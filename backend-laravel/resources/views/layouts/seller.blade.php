<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ?? 'Seller Dashboard' }} | LumBarong</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
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
    </script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=Playfair+Display:wght@700;800&display=swap" rel="stylesheet">
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
            <div class="p-10 flex flex-col h-full">
                <div class="mb-12 shrink-0">
                    <a href="/seller/dashboard" class="font-serif text-lg font-bold text-(--charcoal) tracking-tighter">
                        LUMBARONG
                    </a>
                    <div class="flex items-center gap-1.5 mt-2 px-1 text-(--rust) font-bold tracking-widest text-[10px]">
                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"></path></svg>
                        SELLER SIDE
                    </div>
                </div>

                <nav class="flex-1 space-y-10 overflow-y-auto no-scrollbar">
                    <div class="space-y-4">
                        <div class="text-[10px] font-bold text-(--muted) opacity-60 tracking-widest uppercase px-3">MY SHOP</div>
                        <div class="space-y-1.5">
                             @php
                                $menu = [
                                    ['label' => 'Dashboard', 'path' => 'seller/dashboard', 'icon' => '<path d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"></path>'],
                                    ['label' => 'Products', 'path' => 'seller/products', 'icon' => '<path d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 11m8 4V4"></path>'],
                                    ['label' => 'Messages', 'path' => 'seller/messages', 'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"></path>'],
                                    ['label' => 'Pay Commission', 'path' => 'seller/commission', 'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"></path>'],
                                    ['label' => 'Profile', 'path' => 'seller/profile', 'icon' => '<path d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>'],
                                ];
                            @endphp
                            @foreach($menu as $item)
                                <a href="/{{ $item['path'] }}" class="flex items-center gap-3 px-4 py-3.5 rounded-xl transition-all duration-300 group tracking-wide text-sm font-medium {{ request()->is($item['path'] . '*') ? 'bg-[rgba(192,66,42,0.08)] text-(--rust) border-l-4 border-(--rust)' : 'text-(--charcoal) hover:bg-(--cream) hover:text-(--rust)' }}">
                                    <svg class="w-5 h-5 {{ request()->is($item['path'] . '*') ? 'text-(--rust)' : 'text-(--muted) group-hover:text-(--rust)' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">{!! $item['icon'] !!}</svg>
                                    {{ $item['label'] }}
                                </a>
                            @endforeach
                        </div>
                    </div>

                    <div class="space-y-4">
                        <div class="text-[10px] font-bold text-(--muted) opacity-60 tracking-widest uppercase px-3">SALES</div>
                        <div class="space-y-1.5">
                            <a href="/seller/orders" class="flex items-center gap-3 px-4 py-3.5 rounded-xl transition-all duration-300 group tracking-wide text-sm font-medium {{ request()->is('seller/orders*') ? 'bg-[rgba(192,66,42,0.08)] text-(--rust) border-l-4 border-(--rust)' : 'text-(--charcoal) hover:bg-(--cream) hover:text-(--rust)' }}">
                                <svg class="w-5 h-5 {{ request()->is('seller/orders*') ? 'text-(--rust)' : 'text-(--muted) group-hover:text-(--rust)' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"></path></svg>
                                My Orders
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

        <!-- Main Content Area -->
        <div class="flex-1 flex flex-col h-full relative overflow-hidden">
            <!-- Top Header -->
            <header class="sticky top-0 z-40 bg-white border-b border-(--border) h-18 flex items-center shrink-0">
                <div class="w-full flex items-center justify-between px-4 lg:px-10">
                    <div class="lg:hidden">
                        <a href="/seller/dashboard" class="font-serif text-base font-bold text-(--charcoal) tracking-tighter">LUMBARONG</a>
                    </div>
                    <div class="hidden lg:flex flex-1"></div>
                    <div class="flex items-center gap-4">
                        <!-- Seller Notifications -->
                        <div x-data="{ open: false }" class="relative" @mouseenter="open = true" @mouseleave="open = false">
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
                            <button class="relative w-10 h-10 flex items-center justify-center rounded-xl bg-(--cream) text-(--muted) hover:text-(--rust) transition-all border border-(--border)">
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
                                 class="absolute right-0 mt-2 w-80 bg-white rounded-2xl shadow-2xl border border-gray-100 z-50 overflow-hidden"
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
                                        <a href="{{ $notif->link ?? '#' }}" class="flex items-start gap-3 p-4 hover:bg-gray-50 transition-all border-b border-gray-50 last:border-0">
                                            <div class="w-2 h-2 mt-1.5 rounded-full {{ $notif->isRead ? 'bg-gray-200' : 'bg-red-500' }} shrink-0"></div>
                                            <div class="space-y-0.5">
                                                <div class="text-[11px] font-bold text-black text-left">{{ $notif->title }}</div>
                                                <div class="text-[10px] text-gray-500 text-left leading-relaxed">{{ Str::limit($notif->message, 80) }}</div>
                                                <div class="text-[8px] text-gray-400 text-left">{{ $notif->createdAt->diffForHumans() }}</div>
                                            </div>
                                        </a>
                                    @empty
                                        <div class="p-8 text-center">
                                            <div class="text-xs text-gray-400 italic">No seller notifications yet</div>
                                        </div>
                                    @endforelse
                                </div>
                                <a href="{{ route('seller.notifications.index') }}" class="block w-full py-3 text-center text-[10px] font-bold uppercase tracking-widest text-gray-400 hover:text-black hover:bg-gray-50 border-t border-gray-100 transition-all">View All</a>
                            </div>
                        </div>

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
                        <div class="w-10 h-10 rounded-xl bg-(--charcoal) text-white flex items-center justify-center font-bold shadow-md overflow-hidden">
                            @if(Auth::user()->profilePhoto)
                                <img src="{{ str_starts_with(Auth::user()->profilePhoto, 'http') || str_starts_with(Auth::user()->profilePhoto, '/') ? Auth::user()->profilePhoto : asset('storage/' . Auth::user()->profilePhoto) }}" class="w-full h-full object-cover">
                            @else
                                {{ strtoupper(substr(Auth::user()->name, 0, 1)) }}
                            @endif
                        </div>
                    </div>
                </div>
            </header>

            <!-- Page Content -->
            <main class="flex-1 overflow-y-auto p-4 lg:p-10 pb-24">
                <div class="max-w-300 mx-auto">
                    {{-- Flash Messages (Floating Toasts) --}}
                    @if(session('success') || session('error'))
                    <div 
                        x-data="{ 
                            show: true, 
                            init() { 
                                setTimeout(() => this.show = false, 5000) 
                            } 
                        }"
                        x-show="show"
                        x-transition:enter="transition ease-out duration-300"
                        x-transition:enter-start="opacity-0 translate-y-2 sm:translate-y-0 sm:translate-x-2"
                        x-transition:enter-end="opacity-100 translate-y-0 sm:translate-x-0"
                        x-transition:leave="transition ease-in duration-200"
                        x-transition:leave-start="opacity-100"
                        x-transition:leave-end="opacity-0"
                        class="fixed top-6 right-6 z-9999 w-full max-w-sm bg-white/95 backdrop-blur-md rounded-2xl shadow-2xl border border-gray-100 p-4 flex items-start gap-3.5"
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

            <!-- Mobile Bottom Nav -->
            <nav class="lg:hidden fixed bottom-0 left-0 right-0 bg-white border-t border-(--border) h-18 flex items-center justify-around px-2 z-50">
                <a href="/seller/dashboard" class="flex flex-col items-center gap-1 {{ request()->is('seller/dashboard') ? 'text-(--rust)' : 'text-(--muted)' }}">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"></path></svg>
                    <span class="text-[9px] font-bold uppercase tracking-tighter">Dashboard</span>
                </a>
                <a href="/seller/products" class="flex flex-col items-center gap-1 {{ request()->is('seller/products*') ? 'text-(--rust)' : 'text-(--muted)' }}">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 11m8 4V4"></path></svg>
                    <span class="text-[9px] font-bold uppercase tracking-tighter">Products</span>
                </a>
                <a href="/seller/orders" class="flex flex-col items-center gap-1 {{ request()->is('seller/orders*') ? 'text-(--rust)' : 'text-(--muted)' }}">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"></path></svg>
                    <span class="text-[9px] font-bold uppercase tracking-tighter">Orders</span>
                </a>
                <a href="/seller/messages" class="flex flex-col items-center gap-1 {{ request()->is('seller/messages*') ? 'text-(--rust)' : 'text-(--muted)' }}">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"></path></svg>
                    <span class="text-[9px] font-bold uppercase tracking-tighter">Messages</span>
                </a>
            </nav>
        </div>
    </div>
    
    <x-confirmation-modal />
    @stack('scripts')
</body>
</html>
