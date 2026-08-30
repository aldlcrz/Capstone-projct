<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ?? 'Seller Shop' }} | LumBarong</title>

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
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=Playfair+Display:ital,wght@0,600;0,700;0,800;1,400;1,600&display=swap" rel="stylesheet">
    <script defer src="https://unpkg.com/@alpinejs/collapse@3.x.x/dist/cdn.min.js"></script>
    <script defer src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <style>
        [x-cloak] { display: none !important; }
        :root {
            --gold:         #C49520;
            --deep-gold:    #A16D19;
            --espresso:     #1E1915;
            --warm-cream:   #FFFCF7;
            --parchment:    #FDF8EE;
            --warm-border:  #E8DECB;
            --muted-text:   #766C60;
            --artisan-green:#4A6741;
            --cream:        #FFFCF7;
            --charcoal:     #1E1915;
            --muted:        #766C60;
            --border:       #E8DECB;
        }
        body { font-family: 'Inter', sans-serif; background-color: var(--warm-cream); color: var(--espresso); }
        .font-serif { font-family: 'Playfair Display', serif; }
        .seller-nav-active { background-color: #1E1915 !important; color: #FFFCF7 !important; border-left: 3px solid #C49520 !important; padding-left: calc(0.75rem - 3px) !important; border-radius: 12px; box-shadow: 0 2px 8px rgba(30,25,21,0.08); }
        .seller-nav-active svg { color: #C49520 !important; }
        .seller-nav-inactive { color: #6C6256; }
        .seller-nav-icon-active { color: #C49520; }
        .seller-nav-icon-inactive { color: #9E9182; }
        .drawer-nav-active { background-color: #1E1915 !important; color: #FFFCF7 !important; border-left: 3px solid #C49520 !important; padding-left: calc(0.75rem - 3px) !important; border-radius: 12px; box-shadow: 0 2px 8px rgba(30,25,21,0.08); }
        .drawer-nav-active svg { color: #C49520 !important; }
        .drawer-nav-inactive { color: #6C6256; }
        .drawer-nav-icon-active { color: #C49520; }
        .drawer-nav-icon-inactive { color: #9E9182; }
        .notif-dot-read { background-color: #E8DECB; }
        .notif-dot-unread { background-color: #C49520; }
        .mobile-nav-active { color: #C49520; }
        .mobile-nav-inactive { color: #B0A090; }
        .no-scrollbar::-webkit-scrollbar { display: none; }
        .no-scrollbar { -ms-overflow-style: none; scrollbar-width: none; }
        .artisan-badge { background-color: #C49520; color: #fff; font-size: 9px; font-weight: 800; border-radius: 9999px; padding: 2px 6px; min-width: 16px; text-align: center; }
    </style>
</head>
<body class="antialiased">
    <div x-data="{ isMobileMenuOpen: false }" class="flex h-screen overflow-hidden">

        <!-- DESKTOP SIDEBAR (Light Artisan Theme - Larger Layout) -->
        <aside class="hidden lg:flex flex-col w-80 h-full overflow-hidden shrink-0" style="background-color:#FAF7F2; border-right: 1px solid #E8DECB;">
            <div class="p-8 flex flex-col h-full">

                <!-- Brand -->
                <div class="mb-7 shrink-0">
                    <a href="/seller/dashboard" class="flex items-center gap-3.5 group">
                        <div class="w-11 h-11 rounded-2xl overflow-hidden shadow-xs border shrink-0 flex items-center justify-center" style="border-color: #E8DECB; background: #FFFFFF;">
                            <img src="{{ asset('images/logo-icon.png') }}" alt="LumBarong Logo" class="w-8 h-8 object-contain group-hover:scale-105 transition-transform">
                        </div>
                        <div>
                            <span class="font-serif text-xl font-bold tracking-tight" style="color: #1E1915;">LUMBARONG</span>
                            <div class="flex items-center gap-1.5 mt-0.5">
                                <span style="font-size:10px; font-weight:800; color:#A16D19; letter-spacing:0.18em; text-transform:uppercase;">✦ Artisan Shop</span>
                            </div>
                        </div>
                    </a>
                </div>

                <!-- Navigation -->
                <nav class="flex-1 space-y-6 overflow-y-auto no-scrollbar">
                    @php
                        $sellerAuthId = Auth::id();
                        $unreadMsgCount = 0;
                        $pendingOrdersCount = 0;
                        $attentionProductsCount = 0;
                        try {
                            $unreadMsgCount = \App\Models\Message::where('receiverId', $sellerAuthId)->where('read', false)->count();
                            $pendingOrdersCount = \App\Models\Order::where('sellerId', $sellerAuthId)
                                ->whereIn('status', ['Pending', 'pending', 'To Ship', 'to_ship', 'processing'])
                                ->count();
                            $attentionProductsCount = \App\Models\Product::where('sellerId', $sellerAuthId)
                                ->where(function($q) {
                                    $q->where('stock', '<=', 5)->orWhere('status', 'pending');
                                })
                                ->count();
                        } catch (\Throwable $e) {}

                        $menuGroups = [
                            'SHOP' => [
                                ['label' => 'Dashboard',     'path' => 'seller/dashboard',  'badge' => 0,                       'icon' => '<path d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"></path>'],
                                ['label' => 'Analytics',     'path' => 'seller/analytics',  'badge' => 0,                       'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>'],
                                ['label' => 'Products',      'path' => 'seller/products',   'badge' => $attentionProductsCount,  'icon' => '<path d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 11m8 4V4"></path>'],
                                ['label' => 'Customers',     'path' => 'seller/customers',  'badge' => 0,                       'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path>'],
                                ['label' => 'Messages',      'path' => 'seller/messages',   'badge' => $unreadMsgCount,          'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"></path>'],
                            ],
                            'SALES & ORDERS' => [
                                ['label' => 'My Orders',      'path' => 'seller/orders',    'badge' => $pendingOrdersCount,     'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"></path>'],
                            ],
                            'HERITAGE SHOP' => [
                                ['label' => 'Pay Commission', 'path' => 'seller/commission', 'badge' => 0,                      'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"></path>'],
                                ['label' => 'Shop Policies',  'path' => 'seller/policies',  'badge' => 0,                      'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>'],
                                ['label' => 'Shop Profile',   'path' => 'seller/profile',   'badge' => 0,                      'icon' => '<path d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>'],
                            ],
                        ];
                    @endphp

                    @foreach($menuGroups as $groupLabel => $items)
                        <div class="space-y-1">
                            <div class="text-[9.5px] font-bold tracking-[0.22em] uppercase px-3.5 mb-2" style="color: #A16D19;">{{ $groupLabel }}</div>
                            @foreach($items as $item)
                                @php $isActive = request()->is($item['path'] . '*'); @endphp
                                <a href="/{{ $item['path'] }}"
                                   class="flex items-center justify-between px-3.5 py-3 rounded-2xl transition-all duration-200 tracking-wide text-[13px] font-semibold {{ $isActive ? 'seller-nav-active' : 'seller-nav-inactive' }}"
                                   @if(!$isActive)
                                   onmouseover="this.style.color='#1E1915'; this.style.background='rgba(196,149,32,0.08)';"
                                   onmouseout="this.style.color='#6C6256'; this.style.background='transparent';"
                                   @endif>
                                    <div class="flex items-center gap-3.5">
                                        <svg class="w-4.5 h-4.5 shrink-0 {{ $isActive ? 'seller-nav-icon-active' : 'seller-nav-icon-inactive' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">{!! $item['icon'] !!}</svg>
                                        <span>{{ $item['label'] }}</span>
                                    </div>
                                    @if(($item['badge'] ?? 0) > 0)
                                        <span class="artisan-badge">{{ $item['badge'] }}</span>
                                    @endif
                                </a>
                            @endforeach
                        </div>
                    @endforeach
                </nav>

                <!-- Logout (Red Theme) -->
                <div class="mt-6 pt-5 shrink-0" style="border-top: 1px solid #E8DECB;">
                    <form x-ref="logoutForm" action="{{ route('logout') }}" method="POST">
                        @csrf
                        <button type="button"
                                @click="$dispatch('open-confirmation', {
                                    title: 'Logout',
                                    message: 'Are you sure you want to exit your artisan workspace?',
                                    confirmText: 'Logout',
                                    type: 'danger',
                                    onConfirm: () => $refs.logoutForm.submit()
                                })"
                                class="flex items-center gap-3.5 w-full px-4 py-3 rounded-2xl transition-all text-xs font-bold cursor-pointer shadow-2xs"
                                style="background: #FEF2F2; border: 1px solid #FECACA; color: #DC2626;"
                                onmouseover="this.style.background='#DC2626'; this.style.color='#FFFFFF'; this.style.borderColor='#DC2626';"
                                onmouseout="this.style.background='#FEF2F2'; this.style.color='#DC2626'; this.style.borderColor='#FECACA';">
                            <svg class="w-4.5 h-4.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"></path></svg>
                            <span>Logout Workspace</span>
                        </button>
                    </form>
                </div>
            </div>
        </aside>

        <!-- MAIN WRAPPER -->
        <div class="flex-1 flex flex-col min-w-0 min-h-0 h-full relative overflow-hidden" style="background-color: #FFFCF7;">

            <!-- Top Header (Enlarged & Cleaned) -->
            <header class="sticky top-0 z-40 h-20 flex items-center shrink-0" style="background-color: #FFFCF7; border-bottom: 1px solid #E8DECB;">
                <div class="w-full flex items-center justify-between px-6 lg:px-10">
                    <!-- Mobile: Logo (Side drawer removed) -->
                    <div class="flex items-center gap-3.5 lg:hidden">
                        <a href="/seller/dashboard" class="flex items-center gap-2">
                            <img src="{{ asset('images/logo-icon.png') }}" alt="LumBarong" class="w-8 h-8 object-contain rounded-lg">
                            <span class="font-serif text-base font-bold tracking-tight" style="color: #1E1915;">LUMBARONG</span>
                        </a>
                    </div>
                    <div class="hidden lg:flex flex-1"></div>

                    <!-- Right Side: Notifications + Profile -->
                    <div class="flex items-center gap-3">

                        <!-- Notifications -->
                        <div x-data="{ open: false }" class="relative" @click.away="open = false">
                            @php
                                $unreadCount = 0;
                                $recentNotifications = collect([]);
                                try {
                                    $unreadCount = \App\Models\Notification::where('userId', Auth::id())
                                        ->where('targetRole', 'seller')
                                        ->where('isRead', false)
                                        ->count();
                                    $recentNotifications = \App\Models\Notification::where('userId', Auth::id())
                                        ->where('targetRole', 'seller')
                                        ->orderBy('createdAt', 'desc')
                                        ->limit(5)
                                        ->get();
                                } catch (\Throwable $e) {}
                            @endphp
                            <button @click="open = !open"
                                    class="relative w-11 h-11 flex items-center justify-center rounded-2xl transition-all cursor-pointer shadow-2xs"
                                    style="background: #FDF8EE; border: 1px solid #E8DECB; color: #766C60;"
                                    onmouseover="this.style.color='#C49520'; this.style.borderColor='#C49520';"
                                    onmouseout="this.style.color='#766C60'; this.style.borderColor='#E8DECB';">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"></path>
                                </svg>
                                @if($unreadCount > 0)
                                    <span class="absolute -top-1 -right-1 min-w-5 h-5 px-1.5 text-white text-[10px] font-bold rounded-full flex items-center justify-center border-2 border-white" style="background: #C49520;">{{ $unreadCount }}</span>
                                @endif
                            </button>

                            <!-- Dropdown -->
                            <div x-show="open"
                                 x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 translate-y-1" x-transition:enter-end="opacity-100 translate-y-0"
                                 x-transition:leave="transition ease-in duration-150" x-transition:leave-start="opacity-100 translate-y-0" x-transition:leave-end="opacity-0 translate-y-1"
                                 class="absolute right-0 mt-2 w-72 sm:w-80 rounded-2xl shadow-2xl z-50 overflow-hidden"
                                 style="display:none; background: #FFFCF7; border: 1px solid #E8DECB;" x-cloak>
                                <div class="px-4 py-3 flex items-center justify-between" style="background: #FDF8EE; border-bottom: 1px solid #E8DECB;">
                                    <span class="text-[10px] font-bold uppercase tracking-widest" style="color: #766C60;">Shop Notifications</span>
                                    @if($unreadCount > 0)
                                        <form action="{{ route('seller.notifications.read-all') }}" method="POST" class="inline">
                                            @csrf
                                            <button type="submit" class="text-[9px] font-bold uppercase tracking-widest transition-colors cursor-pointer" style="color: #C49520;" onmouseover="this.style.color='#1E1915';" onmouseout="this.style.color='#C49520';">Mark all read</button>
                                        </form>
                                    @endif
                                </div>
                                <div class="max-h-96 overflow-y-auto no-scrollbar">
                                    @forelse($recentNotifications as $notif)
                                        @if(str_contains(strtolower($notif->title), 'integrity') || str_contains(strtolower($notif->title), 'report') || str_contains($notif->link ?? '', 'report'))
                                            <button type="button"
                                                @click="open = false; fetch('{{ route('notifications.read-and-redirect', $notif->id) }}').catch(()=>{}); window.dispatchEvent(new CustomEvent('open-seller-report', { detail: { id: '{{ Str::after($notif->link ?? '', 'view_report=') ?: 'latest' }}', reason: '{{ addslashes($notif->title) }}', message: '{{ addslashes($notif->message) }}' } }))"
                                                class="w-full text-left flex items-start gap-3 p-4 transition-all border-b last:border-0 cursor-pointer"
                                                style="border-color: #F2ECE1;"
                                                onmouseover="this.style.background='#FDF8EE';" onmouseout="this.style.background='transparent';">
                                                <div class="w-2 h-2 mt-1.5 rounded-full shrink-0 {{ $notif->isRead ? 'notif-dot-read' : 'notif-dot-unread' }}"></div>
                                                <div class="space-y-0.5 flex-1 min-w-0">
                                                    <div class="text-[11px] font-bold text-left flex items-center justify-between" style="color: #1E1915;">
                                                        <span>{{ $notif->title }}</span>
                                                        <span class="text-[8px] font-black uppercase tracking-wider" style="color: #C49520;">View Notice</span>
                                                    </div>
                                                    <div class="text-[10px] text-left leading-relaxed line-clamp-2" style="color: #766C60;">{{ $notif->message }}</div>
                                                    <div class="text-[8px]" style="color: #B0A090;">{{ $notif->createdAt ? \Carbon\Carbon::parse($notif->createdAt)->diffForHumans() : '' }}</div>
                                                </div>
                                            </button>
                                        @else
                                            <a href="{{ route('notifications.read-and-redirect', $notif->id) }}"
                                               class="flex items-start gap-3 p-4 transition-all border-b last:border-0"
                                               style="border-color: #F2ECE1;"
                                               onmouseover="this.style.background='#FDF8EE';" onmouseout="this.style.background='transparent';">
                                                <div class="w-2 h-2 mt-1.5 rounded-full shrink-0 {{ $notif->isRead ? 'notif-dot-read' : 'notif-dot-unread' }}"></div>
                                                <div class="space-y-0.5">
                                                    <div class="text-[11px] font-bold text-left" style="color: #1E1915;">{{ $notif->title }}</div>
                                                    <div class="text-[10px] text-left leading-relaxed" style="color: #766C60;">{{ Str::limit($notif->message, 80) }}</div>
                                                    <div class="text-[8px]" style="color: #B0A090;">{{ $notif->createdAt ? \Carbon\Carbon::parse($notif->createdAt)->diffForHumans() : '' }}</div>
                                                </div>
                                            </a>
                                        @endif
                                    @empty
                                        <div class="p-8 text-center">
                                            <div class="text-xs italic" style="color: #B0A090;">No notifications yet</div>
                                        </div>
                                    @endforelse
                                </div>
                                <a href="{{ route('seller.notifications.index') }}"
                                   class="block w-full py-3 text-center text-[10px] font-bold uppercase tracking-widest transition-all"
                                   style="color: #766C60; border-top: 1px solid #E8DECB;"
                                   onmouseover="this.style.color='#C49520'; this.style.background='#FDF8EE';"
                                   onmouseout="this.style.color='#766C60'; this.style.background='transparent';">View All</a>
                            </div>
                        </div>

                        <!-- Artisan Shop Profile Card (At Notification Icon) -->
                        <a href="{{ route('seller.profile') }}" class="flex items-center gap-3 px-3.5 py-2 rounded-2xl transition-all group shrink-0" style="background: #FFFFFF; border: 1px solid #ECE3D2; box-shadow: 0 2px 6px rgba(0,0,0,0.03);" onmouseover="this.style.borderColor='#C49520';" onmouseout="this.style.borderColor='#ECE3D2';" title="Artisan Shop Profile">
                            <div style="width:36px;height:36px;min-width:36px;border-radius:50%;padding:2px;background:linear-gradient(135deg,#996515,#E6CA65,#996515);box-shadow:0 2px 6px rgba(0,0,0,0.08);" class="shrink-0 group-hover:scale-105 transition-transform">
                                <div style="width:100%;height:100%;border-radius:50%;overflow:hidden;background:#FAF8F5;display:flex;align-items:center;justify-content:center;">
                                    @if(Auth::user()->profile_photo_url)
                                        <img src="{{ Auth::user()->profile_photo_url }}" class="w-full h-full object-cover" onerror="this.style.display='none'">
                                    @else
                                        <span style="font-size:13px;font-weight:800;color:#996515;">{{ strtoupper(substr(Auth::user()->name, 0, 1)) }}</span>
                                    @endif
                                </div>
                            </div>
                            <div class="min-w-0 text-left hidden sm:block pr-1">
                                <div class="text-xs font-bold truncate leading-tight font-sans" style="color: #1E1915;">{{ Auth::user()->name }}</div>
                                <div class="text-[9px] font-extrabold uppercase tracking-wider mt-0.5 font-sans" style="color: #A16D19;">
                                    {{ Auth::user()->isPremiumActive() ? '✦ Premium Artisan' : 'Verified Artisan' }}
                                </div>
                            </div>
                        </a>

                    </div>
                </div>
            </header>

            <!-- Page Content -->
            <main class="flex-1 min-h-0 overflow-y-auto p-3 sm:p-6 lg:p-10 pb-28 lg:pb-12">
                <div class="max-w-7xl mx-auto w-full">
                    {{-- Flash Messages --}}
                    @if(session('success') || session('error'))
                    <div
                        x-data="{ show: true, init() { setTimeout(() => this.show = false, 2800) } }"
                        x-show="show"
                        x-transition:enter="transition ease-out duration-300 transform"
                        x-transition:enter-start="opacity-0 -translate-y-4 -translate-x-1/2"
                        x-transition:enter-end="opacity-100 translate-y-0 -translate-x-1/2"
                        x-transition:leave="transition ease-in duration-200 transform"
                        x-transition:leave-start="opacity-100 translate-y-0 -translate-x-1/2"
                        x-transition:leave-end="opacity-0 -translate-y-4 -translate-x-1/2"
                        class="fixed top-6 left-1/2 -translate-x-1/2 z-9999 w-[calc(100%-2rem)] max-w-sm rounded-2xl shadow-2xl p-4 flex items-start gap-3.5"
                        style="display:none; background: #FFFCF7; border: 1px solid #E8DECB;"
                        x-cloak>
                        @if(session('success'))
                            <div class="w-8 h-8 rounded-full flex items-center justify-center shrink-0 shadow-sm" style="background: #F0F4EF; color: #4A6741; border: 1px solid #C5D9B8;">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"></path></svg>
                            </div>
                            <div class="grow pt-0.5">
                                <h4 class="text-xs font-black uppercase tracking-wider" style="color: #1E1915;">Success</h4>
                                <p class="text-xs font-medium mt-0.5 leading-relaxed" style="color: #766C60;">{{ session('success') }}</p>
                            </div>
                        @endif
                        @if(session('error'))
                            <div class="w-8 h-8 rounded-full flex items-center justify-center shrink-0 shadow-sm" style="background: #FEF2F2; color: #DC2626; border: 1px solid #FECACA;">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12"></path></svg>
                            </div>
                            <div class="grow pt-0.5">
                                <h4 class="text-xs font-black uppercase tracking-wider" style="color: #1E1915;">Error</h4>
                                <p class="text-xs font-medium mt-0.5 leading-relaxed" style="color: #766C60;">{{ session('error') }}</p>
                            </div>
                        @endif
                        <button @click="show = false" class="transition-colors shrink-0" style="color: #E8DECB;" onmouseover="this.style.color='#766C60';" onmouseout="this.style.color='#E8DECB';">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                        </button>
                    </div>
                    @endif
                    @yield('content')
                </div>
            </main>

            <!-- MOBILE BOTTOM NAVBAR (Black text with sharp icons) -->
            <nav class="lg:hidden fixed bottom-0 left-0 right-0 h-16 flex items-center justify-around px-2 z-40 shadow-lg" style="background: #FFFCF7; border-top: 1px solid #E8DECB;">
                <a href="{{ route('seller.dashboard') }}" class="flex-1 flex flex-col items-center justify-center gap-0.5 py-1 transition-colors {{ request()->is('seller/dashboard') || request()->is('seller') ? 'text-[#C49520]' : 'text-[#1E1915]' }}">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l9-9 9 9M5 10v10a1 1 0 001 1h3a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1h3a1 1 0 001-1V10"></path></svg>
                    <span class="text-[10px] font-bold text-[#1E1915]">Home</span>
                </a>
                <a href="{{ route('seller.orders') }}" class="flex-1 flex flex-col items-center justify-center gap-0.5 py-1 relative transition-colors {{ request()->is('seller/orders*') ? 'text-[#C49520]' : 'text-[#1E1915]' }}">
                    <div class="relative">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path></svg>
                        @if(($pendingOrdersCount ?? 0) > 0)
                            <span class="absolute -top-1 -right-2 min-w-3.5 h-3.5 px-1 text-white text-[8px] font-black rounded-full flex items-center justify-center border-2 border-white" style="background: #C49520;">{{ $pendingOrdersCount }}</span>
                        @endif
                    </div>
                    <span class="text-[10px] font-bold text-[#1E1915]">Orders</span>
                </a>
                <a href="{{ route('seller.products.create') }}" class="flex flex-col items-center justify-center -mt-5 group shrink-0 px-2" title="Add New Product">
                    <div class="w-12 h-12 rounded-full flex items-center justify-center shadow-lg transition-all"
                         style="background: #1E1915; color: #FFFCF7;"
                         onmouseover="this.style.background='#C49520';" onmouseout="this.style.background='#1E1915';">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4.5v15m7.5-7.5h-15"></path></svg>
                    </div>
                    <span class="text-[9px] font-bold mt-0.5 text-[#1E1915]">Add</span>
                </a>
                <a href="{{ route('seller.messages') }}" class="flex-1 flex flex-col items-center justify-center gap-0.5 py-1 relative transition-colors {{ request()->is('seller/messages*') ? 'text-[#C49520]' : 'text-[#1E1915]' }}">
                    <div class="relative">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"></path></svg>
                        @if(($unreadMsgCount ?? 0) > 0)
                            <span class="absolute -top-1 -right-2 min-w-3.5 h-3.5 px-1 text-white text-[8px] font-black rounded-full flex items-center justify-center border-2 border-white" style="background: #C49520;">{{ $unreadMsgCount }}</span>
                        @endif
                    </div>
                    <span class="text-[10px] font-bold text-[#1E1915]">Messages</span>
                </a>
                <a href="{{ route('seller.profile') }}" class="flex-1 flex flex-col items-center justify-center gap-0.5 py-1 transition-colors {{ request()->is('seller/profile*') ? 'text-[#C49520]' : 'text-[#1E1915]' }}">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path></svg>
                    <span class="text-[10px] font-bold text-[#1E1915]">Profile</span>
                </a>
            </nav>
        </div>
    </div>

    {{-- Live Notification Popup --}}
    <div
        x-data="{
            popupNotif: null,
            dismissedIds: JSON.parse(sessionStorage.getItem('seen_seller_notif_ids') || '[]'),
            checkNotifications() {
                fetch('/api/notifications?role=seller').then(r => r.json()).then(data => {
                    if (Array.isArray(data)) {
                        const unread = data.filter(n => !n.isRead && !this.dismissedIds.includes(n.id));
                        if (unread.length > 0) {
                            const latest = unread[0];
                            if (!this.popupNotif || this.popupNotif.id !== latest.id) {
                                this.popupNotif = latest;
                                setTimeout(() => { if (this.popupNotif && this.popupNotif.id === latest.id) this.dismiss(); }, 8000);
                            }
                        }
                    }
                }).catch(() => {});
            },
            dismiss() {
                if (this.popupNotif) {
                    this.dismissedIds.push(this.popupNotif.id);
                    sessionStorage.setItem('seen_seller_notif_ids', JSON.stringify(this.dismissedIds));
                    this.popupNotif = null;
                }
            },
            init() { this.checkNotifications(); setInterval(() => this.checkNotifications(), 12000); }
        }"
        x-show="popupNotif" x-cloak
        x-transition:enter="transition ease-out duration-300 transform"
        x-transition:enter-start="opacity-0 -translate-y-4 sm:translate-x-4 sm:-translate-y-0"
        x-transition:enter-end="opacity-100 translate-y-0 sm:translate-x-0"
        x-transition:leave="transition ease-in duration-200 transform"
        x-transition:leave-start="opacity-100 translate-y-0 sm:translate-x-0"
        x-transition:leave-end="opacity-0 -translate-y-4 sm:translate-x-4 sm:-translate-y-0"
        class="fixed top-4 right-4 sm:top-6 sm:right-6 z-9999 max-w-sm w-[calc(100%-2rem)] rounded-2xl shadow-2xl p-4"
        style="display:none; background: #FFFCF7; border: 1px solid #E8DECB;">
        <div class="flex items-start gap-3">
            <div class="w-9 h-9 rounded-xl flex items-center justify-center shrink-0" style="background: #FDF8EE; color: #C49520; border: 1px solid #E8DECB;">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"></path></svg>
            </div>
            <div class="flex-1 min-w-0">
                <div class="flex items-center justify-between gap-1">
                    <h4 class="text-xs font-black truncate uppercase" x-text="popupNotif?.title || 'Notification'" style="color: #1E1915;"></h4>
                    <button @click="dismiss()" class="transition-colors shrink-0" style="color: #E8DECB;" onmouseover="this.style.color='#766C60';" onmouseout="this.style.color='#E8DECB';">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                    </button>
                </div>
                <p class="text-xs font-medium mt-0.5 line-clamp-2 leading-relaxed" x-text="popupNotif?.message" style="color: #766C60;"></p>
                <div class="mt-2.5 flex items-center justify-between">
                    <span class="text-[9px] font-bold uppercase tracking-wider" style="color: #B0A090;">Just now</span>
                    <a :href="'/notifications/' + (popupNotif?.id) + '/read'"
                       class="px-3 py-1 text-white text-[10px] font-bold rounded-lg transition-all shadow-xs inline-flex items-center gap-1"
                       style="background: #1E1915;"
                       onmouseover="this.style.background='#C49520';" onmouseout="this.style.background='#1E1915';">
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
