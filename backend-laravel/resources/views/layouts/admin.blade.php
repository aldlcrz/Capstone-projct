<!DOCTYPE html>
<html lang="en" x-data="adminApp()" :class="{ 'dark': darkMode }" x-init="init()">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ?? 'Admin Panel' }} | LumBarong</title>

    {{-- ① BLOCKING: apply dark class BEFORE first paint so there is no flash --}}
    <script>
        (function () {
            if (localStorage.getItem('adminDarkMode') === 'true') {
                document.documentElement.classList.add('dark');
            }
        })();
    </script>

    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            darkMode: 'class',          /* ② tell Tailwind about class-based dark mode */
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
        :root {
            --rust: #C0420A;
            --cream: #F8F7F4;
            --charcoal: #2A2A2A;
            --muted: #8E8E8E;
            --border: #E5E5E5;
        }
        /* ── Dark mode overrides ─────────────────────────────────── */
        .dark {
            --cream: #1E1E2E;
            --charcoal: #F0EEE9;
            --muted: #9A9AB0;
            --border: #2E2E42;
        }
        .dark body { background-color: #13131F; }
        .dark aside { background-color: #1A1A2B !important; border-color: #2E2E42 !important; }
        .dark header { background-color: #1A1A2B !important; border-color: #2E2E42 !important; }
        .dark main { background-color: #13131F !important; }
        /* Dark mode toggle track */
        .toggle-track {
            width: 40px; height: 22px;
            border-radius: 999px;
            transition: background 0.3s;
            position: relative;
            cursor: pointer;
            flex-shrink: 0;
        }
        .toggle-thumb {
            width: 16px; height: 16px;
            border-radius: 50%;
            background: white;
            position: absolute;
            top: 3px; left: 3px;
            transition: transform 0.25s cubic-bezier(.4,0,.2,1), box-shadow 0.25s;
            box-shadow: 0 1px 3px rgba(0,0,0,.3);
        }
        body { font-family: 'Inter', sans-serif; background-color: #F7F3EE; }
        .font-serif { font-family: 'Playfair Display', serif; }
        .no-scrollbar::-webkit-scrollbar { display: none; }
        .no-scrollbar { -ms-overflow-style: none; scrollbar-width: none; }
        [x-cloak] { display: none !important; }
    </style>
</head>
<body class="antialiased text-(--charcoal)">
    <div x-data="{ isMobileMenuOpen: false }" class="flex h-screen overflow-hidden">
        
        <!-- Desktop Sidebar -->
        <aside class="hidden lg:flex flex-col w-[280px] h-full bg-white border-r border-(--border) overflow-hidden">
            <div class="p-10 flex flex-col h-full">
                <div class="mb-12 shrink-0">
                    <a href="/admin/dashboard" class="font-serif text-lg font-bold text-(--charcoal) tracking-tighter">
                        LUMBARONG
                    </a>
                    <div class="flex items-center gap-1.5 mt-2 px-1 text-(--rust) font-bold tracking-widest text-[10px]">
                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"></path></svg>
                        CONTROL PANEL
                    </div>
                </div>

                <nav class="flex-1 space-y-6 overflow-y-auto no-scrollbar">
                    @php
                        $sidebarGroups = [
                            'OVERVIEW' => [
                                ['label' => 'Dashboard', 'path' => 'admin/dashboard', 'icon' => '<path d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>']
                            ],
                            'USER REGISTRY' => [
                                ['label' => 'Users',   'path' => 'admin/users',   'icon' => '<path d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path>'],
                                ['label' => 'Sellers', 'path' => 'admin/sellers', 'icon' => '<path d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"></path>'],
                                [
                                    'label' => 'Subscriptions',
                                    'path'  => 'admin/subscriptions',
                                    'icon'  => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4M7.835 4.697a3.42 3.42 0 001.946-.806 3.42 3.42 0 014.438 0 3.42 3.42 0 001.946.806 3.42 3.42 0 013.138 3.138 3.42 3.42 0 00.806 1.946 3.42 3.42 0 010 4.438 3.42 3.42 0 00-.806 1.946 3.42 3.42 0 01-3.138 3.138 3.42 3.42 0 00-1.946.806 3.42 3.42 0 01-4.438 0 3.42 3.42 0 00-1.946-.806 3.42 3.42 0 01-3.138-3.138 3.42 3.42 0 00-.806-1.946 3.42 3.42 0 010-4.438 3.42 3.42 0 00.806-1.946 3.42 3.42 0 013.138-3.138z"></path>',
                                    'badge' => \App\Models\SellerSubscription::where('status', 'pending')->count()
                                ]
                            ],
                            'SYSTEM GOVERNANCE' => [
                                ['label' => 'Reports', 'path' => 'admin/reports', 'icon' => '<path d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>']
                            ],
                            'PRODUCT CONTROL' => [
                                ['label' => 'Products',   'path' => 'admin/products',   'icon' => '<path d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"></path>'],
                                ['label' => 'Categories', 'path' => 'admin/categories', 'icon' => '<path d="M4 6h16M4 10h16M4 14h16M4 18h16"></path>']
                            ],
                            'CONTENT MANAGEMENT' => [
                                [
                                    'label' => 'Hero Banners',
                                    'path'  => 'admin/banners',
                                    'icon'  => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path>',
                                    'badge' => \App\Models\Banner::whereNotNull('userId')->where('status','pending')->count()
                                ]
                            ],
                            'SETTINGS' => [
                                ['label' => 'Maintenance',      'path' => 'admin/maintenance', 'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z"></path>'],
                                ['label' => 'Audit Logs',       'path' => 'admin/audit-logs',  'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"></path>'],
                                ['label' => 'Platform Info',    'path' => 'admin/platform',    'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>'],
                            ],
                        ];
                    @endphp

                    @foreach($sidebarGroups as $group => $items)
                        @if(!$loop->first)
                            <div class="border-t border-(--border)"></div>
                        @endif
                        <div class="space-y-1">
                            <div class="text-[10px] font-bold text-(--muted) opacity-60 tracking-widest uppercase px-3 mb-2">{{ $group }}</div>
                            @foreach($items as $item)
                                <a href="/{{ $item['path'] }}"
                                    class="flex items-center justify-between px-4 py-3.5 rounded-xl transition-all duration-300 group tracking-wide text-sm font-medium {{ request()->is($item['path'] . '*') ? 'bg-[rgba(192,66,42,0.08)] text-(--rust) border-l-4 border-(--rust)' : 'text-(--charcoal) hover:bg-(--cream) hover:text-(--rust)' }}">
                                    <div class="flex items-center gap-3">
                                        <svg class="w-5 h-5 {{ request()->is($item['path'] . '*') ? 'text-(--rust)' : 'text-(--muted) group-hover:text-(--rust)' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">{!! $item['icon'] !!}</svg>
                                        {{ $item['label'] }}
                                    </div>
                                    @if(isset($item['badge']) && $item['badge'] > 0)
                                        <span class="px-2 py-0.5 bg-red-500 text-white text-[9px] font-bold rounded-full border border-white shrink-0">{{ $item['badge'] }}</span>
                                    @endif
                                </a>
                            @endforeach
                        </div>
                    @endforeach
                </nav>

                <div class="mt-6 pt-6 border-t border-(--border) shrink-0 space-y-3">
                    {{-- Dark Mode Toggle --}}
                    <button
                        @click="toggleDark()"
                        class="flex items-center justify-between w-full px-4 py-3 rounded-xl transition-all duration-300 hover:bg-(--cream) group"
                        title="Toggle dark mode"
                    >
                        <div class="flex items-center gap-3">
                            {{-- Sun icon (shown in dark mode) --}}
                            <svg x-show="darkMode" class="w-5 h-5 text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364-6.364l-.707.707M6.343 17.657l-.707.707M17.657 17.657l-.707-.707M6.343 6.343l-.707-.707M12 8a4 4 0 100 8 4 4 0 000-8z"/>
                            </svg>
                            {{-- Moon icon (shown in light mode) --}}
                            <svg x-show="!darkMode" class="w-5 h-5 text-(--muted) group-hover:text-(--rust)" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z"/>
                            </svg>
                            <span class="text-sm font-medium text-(--charcoal) group-hover:text-(--rust) transition-colors" x-text="darkMode ? 'Light Mode' : 'Dark Mode'"></span>
                        </div>
                        {{-- Toggle track --}}
                        <div class="toggle-track" :style="darkMode ? 'background:#C0420A' : 'background:#D1D5DB'">
                            <div class="toggle-thumb" :style="darkMode ? 'transform:translateX(18px)' : ''"></div>
                        </div>
                    </button>

                    {{-- User card --}}
                    <div class="flex items-center gap-3 px-2">
                        <div class="w-10 h-10 rounded-xl bg-(--charcoal) text-white flex items-center justify-center font-bold">
                            {{ strtoupper(substr(Auth::user()->name, 0, 1)) }}
                        </div>
                        <div>
                            <div class="text-sm font-bold">{{ Auth::user()->name }}</div>
                            <div class="text-[10px] text-(--muted) font-bold uppercase tracking-widest leading-none">Administrator</div>
                        </div>
                    </div>
                    <form action="{{ route('logout') }}" method="POST">
                        @csrf
                        <button type="submit" class="flex items-center gap-3 w-full px-4 py-3.5 bg-red-50 text-red-600 rounded-xl hover:bg-red-100 transition-all font-bold text-xs tracking-widest uppercase">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"></path></svg>
                            Logout
                        </button>
                    </form>
                </div>
            </div>
        </aside>

        <!-- Main Content Area -->
        <div class="flex-1 flex flex-col h-full relative overflow-hidden">
            <!-- Header -->
            <header class="sticky top-0 z-40 bg-white border-b border-(--border) h-[72px] flex items-center shrink-0 px-4 lg:px-10 justify-between">
                <div class="lg:hidden font-serif font-bold text-(--charcoal) tracking-tighter">LUMBARONG ADMIN</div>
                <div class="hidden lg:block"></div>
                <div class="flex items-center gap-4">
                    <!-- Admin Notifications -->
                    <div x-data="{ open: false }" class="relative" @mouseenter="open = true" @mouseleave="open = false">
                        @php
                            $unreadCount = \App\Models\Notification::where('userId', Auth::id())
                                ->where('targetRole', 'admin')
                                ->where('isRead', false)
                                ->count();
                            $recentNotifications = \App\Models\Notification::where('userId', Auth::id())
                                ->where('targetRole', 'admin')
                                ->orderBy('createdAt', 'desc')
                                ->limit(5)
                                ->get();
                        @endphp
                        <button class="relative w-10 h-10 flex items-center justify-center rounded-xl bg-(--cream) text-(--muted) hover:text-(--rust) transition-all border border-(--border)">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"></path>
                            </svg>
                            @if($unreadCount > 0)
                                <span class="absolute -top-1 -right-1 min-w-[18px] h-[18px] px-1 bg-red-500 text-white text-[9px] font-bold rounded-full flex items-center justify-center border-2 border-white">{{ $unreadCount }}</span>
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
                                <span class="text-[10px] font-bold uppercase tracking-widest text-gray-400">Admin Notifications</span>
                                @if($unreadCount > 0)
                                    <form action="{{ route('admin.notifications.read-all') }}" method="POST" class="inline">
                                        @csrf
                                        <button type="submit" class="text-[9px] font-bold uppercase tracking-widest text-[#C0420A] hover:text-black transition-colors">Mark all read</button>
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
                                        <div class="text-xs text-gray-400 italic">No admin notifications yet</div>
                                    </div>
                                @endforelse
                            </div>
                            <a href="{{ route('admin.notifications.index') }}" class="block w-full py-3 text-center text-[10px] font-bold uppercase tracking-widest text-gray-400 hover:text-black hover:bg-gray-50 border-t border-gray-100 transition-all">View All</a>
                        </div>
                    </div>
                    <div class="text-sm font-bold text-(--muted) hidden sm:block">
                        {{ date('M d, Y') }}
                    </div>
                </div>
            </header>

            <!-- Page Content -->
            <main class="flex-1 overflow-y-auto p-4 lg:p-10 pb-24">
                <div class="max-w-[1200px] mx-auto">
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
        </div>
    </div>
    
    <x-confirmation-modal />
    <x-broadcast-notification />
    @stack('scripts')
    <script>
        /* ③ dark mode CSS injected AFTER Tailwind CDN — always wins the cascade */
        (function () {
            var DARK_CSS = [
                /* ── Layout ─────────────────────────────────────────── */
                'html.dark body{background-color:#13131F}',
                'html.dark aside{background-color:#1A1A2B!important;border-color:#2E2E42!important}',
                'html.dark header{background-color:#1A1A2B!important;border-color:#2E2E42!important}',
                'html.dark main{background-color:#13131F!important}',
                /* ── Panels / cards ──────────────────────────────────── */
                'html.dark .bg-white{background-color:#1E1E2E!important}',
                'html.dark .bg-white\/95{background-color:rgba(30,30,46,.97)!important}',
                'html.dark .bg-gray-50{background-color:#18182A!important}',
                'html.dark .bg-gray-100{background-color:#252538!important}',
                'html.dark .bg-gray-200{background-color:#2E2E42!important}',
                'html.dark .bg-gray-300{background-color:#3A3A54!important}',
                /* ── Borders ─────────────────────────────────────────── */
                'html.dark .border-gray-50,html.dark .border-gray-100,html.dark .border-gray-200{border-color:#2E2E42!important}',
                'html.dark .divide-y>*+*,html.dark .divide-gray-100>*+*{border-color:#2E2E42!important}',
                /* ── Text ────────────────────────────────────────────── */
                'html.dark .text-black{color:#E8E6E1!important}',
                'html.dark .text-gray-300{color:#5A5A7A!important}',
                'html.dark .text-gray-400{color:#7A7A9A!important}',
                'html.dark .text-gray-500{color:#8A8AA8!important}',
                'html.dark .text-gray-600{color:#9A9AB8!important}',
                'html.dark .text-gray-700{color:#B0B0D0!important}',
                'html.dark .text-gray-800{color:#D0CEE8!important}',
                'html.dark .text-gray-900{color:#E8E6F8!important}',
                /* ── Inputs ──────────────────────────────────────────── */
                'html.dark input[type=text],html.dark input[type=email],html.dark input[type=number],html.dark input[type=password],html.dark input[type=search],html.dark input[type=date],html.dark textarea,html.dark select{background-color:#252538!important;border-color:#2E2E42!important;color:#E8E6E1!important}',
                /* ── Tables ─────────────────────────────────────────── */
                'html.dark table{background-color:#1E1E2E}',
                'html.dark thead,html.dark thead tr{background-color:#191929!important}',
                'html.dark tbody tr:hover{background-color:rgba(255,255,255,.035)!important}',
                'html.dark td,html.dark th{border-color:#2E2E42!important}',
                /* ── Hover states ────────────────────────────────────── */
                'html.dark .hover\\:bg-gray-50:hover{background-color:rgba(255,255,255,.05)!important}',
                'html.dark .hover\\:bg-gray-100:hover{background-color:rgba(255,255,255,.08)!important}',
                'html.dark .hover\\:shadow-md:hover{box-shadow:0 4px 16px rgba(0,0,0,.5)!important}',
                /* ── Dropdowns / modals ──────────────────────────────── */
                'html.dark .shadow-2xl.rounded-2xl,html.dark .shadow-xl.rounded-2xl,html.dark .shadow-lg.rounded-2xl{background-color:#1E1E2E!important}',
                /* ── Shadows ─────────────────────────────────────────── */
                'html.dark .shadow-sm{box-shadow:0 1px 6px rgba(0,0,0,.45)!important}',
                /* ── Amber alerts ────────────────────────────────────── */
                'html.dark .bg-amber-50{background-color:rgba(251,191,36,.10)!important}',
                'html.dark .border-amber-200{border-color:rgba(251,191,36,.3)!important}',
                'html.dark .text-amber-700{color:#FBB924!important}',
                /* ── Red / danger ────────────────────────────────────── */
                'html.dark .bg-red-50{background-color:rgba(239,68,68,.12)!important}',
            ].join('\n');

            var el = document.getElementById('admin-dark-overrides');
            if (!el) {
                el = document.createElement('style');
                el.id = 'admin-dark-overrides';
                document.head.appendChild(el);
            }
            el.textContent = DARK_CSS;
        })();

        function adminApp() {
            return {
                darkMode: false,
                isMobileMenuOpen: false,
                init() {
                    this.darkMode = localStorage.getItem('adminDarkMode') === 'true';
                },
                toggleDark() {
                    this.darkMode = !this.darkMode;
                    localStorage.setItem('adminDarkMode', this.darkMode);
                }
            }
        }
    </script>
</body>
</html>
