<!DOCTYPE html>
<html lang="en" x-data="adminApp()" x-init="init()">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ?? 'Admin Panel' }} | LumBarong</title>

    <!-- Favicon -->
    <link rel="icon" type="image/x-icon" href="{{ asset('favicon.ico') }}">
    <link rel="apple-touch-icon" sizes="180x180" href="{{ asset('images/apple-touch-icon.png') }}">

    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        rust: '#C0420A',
                        cream: '#F8F7F4',
                        charcoal: '#1F2937',
                        muted: '#4B5563',
                        border: '#E5E7EB',
                    },
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
    <script defer src="https://unpkg.com/@alpinejs/collapse@3.x.x/dist/cdn.min.js"></script>
    <script defer src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <style>
        :root {
            --rust: #C0420A;
            --cream: #F8F7F4;
            --charcoal: #1F2937;
            --muted: #4B5563;
            --border: #E5E7EB;
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
        <aside class="hidden lg:flex flex-col w-70 h-full bg-white border-r border-(--border) overflow-hidden">
            <div class="p-8 flex flex-col h-full">
                <div class="mb-10 shrink-0">
                    <a href="/admin/dashboard" class="flex items-center gap-3 group">
                        <img src="{{ asset('images/logo-icon.png') }}" alt="LumBarong Logo" class="w-9 h-9 object-contain rounded-full shadow-xs group-hover:scale-105 transition-transform">
                        <div>
                            <span class="font-serif text-lg font-bold text-(--charcoal) tracking-tight">LUMBARONG</span>
                            <div class="flex items-center gap-1.5 px-0.5 text-(--rust) font-bold tracking-widest text-[9px]">
                                <svg class="w-2.5 h-2.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"></path></svg>
                                CONTROL PANEL
                            </div>
                        </div>
                    </a>
                </div>

                <nav class="flex-1 space-y-6 overflow-y-auto no-scrollbar">
                    @php
                        $sidebarGroups = [
                            'OVERVIEW' => [
                                ['label' => 'Dashboard', 'path' => 'admin/dashboard', 'icon' => '<path d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>']
                            ],
                            'USER REGISTRY' => [
                                ['label' => 'Users',   'path' => 'admin/users',   'icon' => '<path d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path>'],
                                ['label' => 'Sellers', 'path' => 'admin/sellers', 'icon' => '<path d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"></path>']
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
                                    'label' => 'Promotions',
                                    'path'  => 'admin/banners',
                                    'icon'  => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path>',
                                    'badge' => \App\Models\Banner::whereNotNull('userId')->where('status','pending')->count()
                                ]
                            ]
                        ];
                    @endphp

                    @foreach($sidebarGroups as $group => $items)
                        @if(!$loop->first)
                            <div class="border-t border-(--border)"></div>
                        @endif
                        <div class="space-y-1">
                            <div class="text-[10px] font-black text-gray-500 tracking-widest uppercase px-3 mb-2">{{ $group }}</div>
                            @foreach($items as $item)
                                <a href="/{{ $item['path'] }}"
                                    class="flex items-center justify-between px-4 py-3.5 rounded-xl transition-all duration-300 group tracking-wide text-sm font-medium {{ request()->is($item['path'] . '*') ? 'bg-[rgba(192,66,42,0.08)] text-(--rust) border-l-4 border-(--rust)' : 'text-(--charcoal) hover:bg-(--cream) hover:text-(--rust)' }}">
                                    <div class="flex items-center gap-3">
                                        <svg class="w-5 h-5 {{ request()->is($item['path'] . '*') ? 'text-(--rust)' : 'text-gray-500 group-hover:text-(--rust)' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">{!! $item['icon'] !!}</svg>
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
                    {{-- User card --}}
                    <div class="flex items-center gap-3 px-2">
                        <div class="w-10 h-10 rounded-xl bg-(--charcoal) text-white flex items-center justify-center font-bold">
                            {{ strtoupper(substr(in_array(Auth::user()->name, ['Super Admin', 'LumBarong Admin']) ? 'LumBarong' : Auth::user()->name, 0, 1)) }}
                        </div>
                        <div>
                            <div class="text-sm font-bold">{{ in_array(Auth::user()->name, ['Super Admin', 'LumBarong Admin']) ? 'LumBarong' : Auth::user()->name }}</div>
                            <div class="text-[10px] text-(--muted) font-bold uppercase tracking-widest leading-none">Administrator</div>
                        </div>
                    </div>
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
            <!-- Header -->
            <header class="sticky top-0 z-40 bg-white border-b border-(--border) h-16 lg:h-18 flex items-center shrink-0 px-4 lg:px-10 justify-between">
                <div class="flex items-center gap-2">
                    <a href="/admin/dashboard" class="lg:hidden flex items-center gap-2">
                        <img src="{{ asset('images/logo-icon.png') }}" alt="LumBarong" class="w-7 h-7 object-contain rounded-full shadow-xs">
                        <span class="font-serif font-bold text-[#2A2A2A] tracking-tight text-base">LUMBARONG</span>
                        <span class="text-[9px] font-black text-[#C0420A] px-1.5 py-0.5 bg-[#C0420A]/10 rounded uppercase">Admin</span>
                    </a>
                </div>
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
                    
                    <!-- Admin Profile Dropdown with Overflow Tools -->
                    <div x-data="{ profileOpen: false }" class="relative" @click.away="profileOpen = false">
                        <button @click="profileOpen = !profileOpen" class="flex items-center gap-3 hover:opacity-80 transition-all cursor-pointer focus:outline-none" title="Admin Profile & Settings">
                            <div class="text-right hidden sm:block">
                                <div class="text-sm font-bold text-gray-900 flex items-center gap-1.5 justify-end">
                                    {{ in_array(Auth::user()->name, ['Super Admin', 'LumBarong Admin']) ? 'LumBarong' : Auth::user()->name }}
                                    <span class="text-xs text-[#C0420A]" title="System Administrator">🛡️</span>
                                </div>
                                <div class="text-[10px] font-bold uppercase tracking-widest text-[#C0420A]">
                                    Administrator
                                </div>
                            </div>
                            <div class="w-9 h-9 sm:w-10 sm:h-10 rounded-xl bg-black text-white flex items-center justify-center font-bold shadow-md overflow-hidden shrink-0 border-2 border-white">
                                @if(Auth::user()->profilePhoto)
                                    <img src="{{ str_starts_with(Auth::user()->profilePhoto, 'http') || str_starts_with(Auth::user()->profilePhoto, '/') ? Auth::user()->profilePhoto : asset('storage/' . Auth::user()->profilePhoto) }}" class="w-full h-full object-cover" onerror="this.style.display='none'">
                                @else
                                    {{ strtoupper(substr(in_array(Auth::user()->name, ['Super Admin', 'LumBarong Admin']) ? 'LumBarong' : Auth::user()->name, 0, 1)) }}
                                @endif
                            </div>
                            <svg class="w-4 h-4 text-gray-400 hidden sm:block transition-transform duration-200" :class="{ 'rotate-180': profileOpen }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                            </svg>
                        </button>

                        <!-- Admin Profile & Overflow Menu Dropdown Panel -->
                        <div x-show="profileOpen"
                             x-transition:enter="transition ease-out duration-200"
                             x-transition:enter-start="opacity-0 translate-y-1 scale-95"
                             x-transition:enter-end="opacity-100 translate-y-0 scale-100"
                             x-transition:leave="transition ease-in duration-150"
                             x-transition:leave-start="opacity-100 translate-y-0 scale-100"
                             x-transition:leave-end="opacity-0 translate-y-1 scale-95"
                             class="absolute right-0 mt-2 w-64 bg-white rounded-2xl shadow-2xl border border-gray-100 z-50 overflow-hidden"
                             style="display: none;"
                             x-cloak>
                            
                            <!-- Header / Admin Info -->
                            <div class="px-4 py-3.5 bg-gray-50 border-b border-gray-100 flex items-center gap-3">
                                <div class="w-9 h-9 rounded-xl bg-black text-white flex items-center justify-center font-bold text-xs shrink-0">
                                    {{ strtoupper(substr(in_array(Auth::user()->name, ['Super Admin', 'LumBarong Admin']) ? 'LumBarong' : Auth::user()->name, 0, 1)) }}
                                </div>
                                <div class="min-w-0">
                                    <div class="text-xs font-bold text-gray-900 truncate">{{ in_array(Auth::user()->name, ['Super Admin', 'LumBarong Admin']) ? 'LumBarong' : Auth::user()->name }}</div>
                                    <div class="text-[10px] text-gray-400 truncate">{{ Auth::user()->email }}</div>
                                </div>
                            </div>

                            <!-- Overflow Administrative Features & Settings -->
                            <div class="py-1">
                                <div class="px-4 py-1.5 text-[9px] font-bold uppercase tracking-widest text-gray-400">System Utilities</div>

                                <a href="{{ route('admin.maintenance') }}" class="flex items-center gap-2.5 px-4 py-2.5 text-xs font-semibold text-gray-700 hover:bg-[#C0420A]/10 hover:text-[#C0420A] transition-colors">
                                    <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z"></path></svg>
                                    <span>Maintenance Mode</span>
                                </a>

                                <a href="{{ route('admin.audit-logs') }}" class="flex items-center gap-2.5 px-4 py-2.5 text-xs font-semibold text-gray-700 hover:bg-[#C0420A]/10 hover:text-[#C0420A] transition-colors">
                                    <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"></path></svg>
                                    <span>Audit Logs</span>
                                </a>

                                <a href="{{ route('admin.platform') }}" class="flex items-center gap-2.5 px-4 py-2.5 text-xs font-semibold text-gray-700 hover:bg-[#C0420A]/10 hover:text-[#C0420A] transition-colors">
                                    <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                                    <span>Platform Info</span>
                                </a>

                                <a href="{{ route('admin.export') }}" class="flex items-center gap-2.5 px-4 py-2.5 text-xs font-semibold text-gray-700 hover:bg-[#C0420A]/10 hover:text-[#C0420A] transition-colors">
                                    <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path></svg>
                                    <span>Export Global Report</span>
                                </a>
                            </div>

                            <div class="p-2 border-t border-gray-100 bg-gray-50/50">
                                <form x-ref="dropdownLogoutForm" action="{{ route('logout') }}" method="POST">
                                    @csrf
                                    <button type="button"
                                            @click="$dispatch('open-confirmation', {
                                                title: 'Logout',
                                                message: 'Are you sure you want to logout?',
                                                confirmText: 'Logout',
                                                type: 'danger',
                                                onConfirm: () => $refs.dropdownLogoutForm.submit()
                                            })"
                                            class="flex items-center justify-center gap-2 w-full py-2 bg-red-50 text-red-600 rounded-xl hover:bg-red-100 transition-all font-bold text-[10px] tracking-widest uppercase cursor-pointer">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"></path></svg>
                                        Logout
                                    </button>
                                </form>
                            </div>
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

            <!-- Admin Mobile Fixed Bottom Navigation Bar -->
            <nav class="lg:hidden fixed bottom-0 left-0 right-0 bg-white border-t border-gray-200/80 h-16 flex items-center justify-around px-2 z-40 shadow-lg">
                <!-- Dashboard -->
                <a href="{{ route('admin.dashboard') }}" class="flex flex-col items-center gap-0.5 px-2.5 py-1 {{ request()->is('admin/dashboard') ? 'text-[#C0420A]' : 'text-gray-500 hover:text-gray-700' }}">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M3 12l9-9 9 9M5 10v10a1 1 0 001 1h3a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1h3a1 1 0 001-1V10"></path>
                    </svg>
                    <span class="text-[9px] font-semibold">Dashboard</span>
                </a>

                <!-- Users -->
                <a href="{{ route('admin.users') }}" class="flex flex-col items-center gap-0.5 px-2.5 py-1 {{ request()->is('admin/users*') ? 'text-[#C0420A]' : 'text-gray-500 hover:text-gray-700' }}">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path>
                    </svg>
                    <span class="text-[9px] font-semibold">Users</span>
                </a>

                <!-- Sellers -->
                <a href="{{ route('admin.sellers') }}" class="flex flex-col items-center gap-0.5 px-2.5 py-1 {{ request()->is('admin/sellers*') ? 'text-[#C0420A]' : 'text-gray-500 hover:text-gray-700' }}">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"></path>
                    </svg>
                    <span class="text-[9px] font-semibold">Sellers</span>
                </a>

                <!-- Products -->
                <a href="{{ route('admin.products') }}" class="flex flex-col items-center gap-0.5 px-2.5 py-1 {{ request()->is('admin/products*') ? 'text-[#C0420A]' : 'text-gray-500 hover:text-gray-700' }}">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"></path>
                    </svg>
                    <span class="text-[9px] font-semibold">Products</span>
                </a>

                <!-- Admin Profile / More Tools Sheet -->
                <div x-data="{ mobileMoreOpen: false }" class="relative">
                    <button @click="mobileMoreOpen = !mobileMoreOpen" class="flex flex-col items-center gap-0.5 px-2.5 py-1 text-gray-500 hover:text-gray-700 cursor-pointer">
                        <div class="w-6 h-6 rounded-full bg-black text-white flex items-center justify-center text-[10px] font-bold overflow-hidden border border-white">
                            {{ strtoupper(substr(Auth::user()->name, 0, 1)) }}
                        </div>
                        <span class="text-[9px] font-semibold">More ▾</span>
                    </button>

                    <!-- Admin Profile Overflow Menu Popup -->
                    <div x-show="mobileMoreOpen"
                         @click.away="mobileMoreOpen = false"
                         x-transition:enter="transition ease-out duration-200"
                         x-transition:enter-start="opacity-0 translate-y-4 scale-95"
                         x-transition:enter-end="opacity-100 translate-y-0 scale-100"
                         x-transition:leave="transition ease-in duration-150"
                         x-transition:leave-start="opacity-100 translate-y-0 scale-100"
                         x-transition:leave-end="opacity-0 translate-y-4 scale-95"
                         class="absolute right-0 bottom-14 w-60 bg-white rounded-2xl shadow-2xl border border-gray-100 z-50 overflow-hidden"
                         style="display: none;"
                         x-cloak>
                        
                        <div class="px-4 py-3 bg-gray-50 border-b border-gray-100">
                            <div class="text-xs font-bold text-gray-900 truncate">{{ in_array(Auth::user()->name, ['Super Admin', 'LumBarong Admin']) ? 'LumBarong' : Auth::user()->name }}</div>
                            <div class="text-[9px] font-bold text-[#C0420A] uppercase tracking-wider">System Administrator</div>
                        </div>

                        <div class="py-1">
                            <a href="{{ route('admin.categories.index') }}" class="flex items-center gap-2.5 px-4 py-2 text-xs font-semibold text-gray-700 hover:bg-gray-50">
                                <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 10h16M4 14h16M4 18h16"></path></svg>
                                <span>Categories</span>
                            </a>
                            <a href="{{ route('admin.banners.index') }}" class="flex items-center gap-2.5 px-4 py-2 text-xs font-semibold text-gray-700 hover:bg-gray-50">
                                <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                                <span>Promotions</span>
                            </a>
                            <a href="{{ route('admin.reports') }}" class="flex items-center gap-2.5 px-4 py-2 text-xs font-semibold text-gray-700 hover:bg-gray-50">
                                <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
                                <span>Reports</span>
                            </a>
                            <a href="{{ route('admin.maintenance') }}" class="flex items-center gap-2.5 px-4 py-2 text-xs font-semibold text-gray-700 hover:bg-gray-50">
                                <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z"></path></svg>
                                <span>Maintenance</span>
                            </a>
                            <a href="{{ route('admin.audit-logs') }}" class="flex items-center gap-2.5 px-4 py-2 text-xs font-semibold text-gray-700 hover:bg-gray-50">
                                <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"></path></svg>
                                <span>Audit Logs</span>
                            </a>
                            <a href="{{ route('admin.platform') }}" class="flex items-center gap-2.5 px-4 py-2 text-xs font-semibold text-gray-700 hover:bg-gray-50">
                                <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                                <span>Platform Info</span>
                            </a>
                        </div>

                        <div class="p-2 border-t border-gray-100 bg-gray-50">
                            <form x-ref="mobileBottomLogoutForm" action="{{ route('logout') }}" method="POST">
                                @csrf
                                <button type="button"
                                        @click="$dispatch('open-confirmation', {
                                            title: 'Logout',
                                            message: 'Are you sure you want to logout?',
                                            confirmText: 'Logout',
                                            type: 'danger',
                                            onConfirm: () => $refs.mobileBottomLogoutForm.submit()
                                        })"
                                        class="flex items-center justify-center gap-2 w-full py-2 bg-red-50 text-red-600 rounded-xl font-bold text-[10px] tracking-widest uppercase cursor-pointer">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"></path></svg>
                                    Logout
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </nav>
        </div>
    </div>
    
    <x-confirmation-modal />
    <x-broadcast-notification />
    <x-modal-scroll-lock />
    @stack('scripts')
    <script>
        function adminApp() {
            return {
                isMobileMenuOpen: false,
                init() {
                    localStorage.removeItem('adminDarkMode');
                    document.documentElement.classList.remove('dark');
                }
            }
        }
    </script>
</body>
</html>
