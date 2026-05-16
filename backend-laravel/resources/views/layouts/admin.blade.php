<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ?? 'Admin Panel' }} | LumBarong</title>
    <script src="https://cdn.tailwindcss.com"></script>
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
        body { font-family: 'Inter', sans-serif; background-color: #F7F3EE; }
        .font-serif { font-family: 'Playfair Display', serif; }
        .no-scrollbar::-webkit-scrollbar { display: none; }
        .no-scrollbar { -ms-overflow-style: none; scrollbar-width: none; }
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

                <nav class="flex-1 space-y-10 overflow-y-auto no-scrollbar">
                    @php
                        $sidebarGroups = [
                            'SYSTEM STATUS' => [
                                ['label' => 'Dashboard', 'path' => 'admin/dashboard', 'icon' => '<path d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>']
                            ],
                            'USER REGISTRY' => [
                                ['label' => 'Users', 'path' => 'admin/users', 'icon' => '<path d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path>'],
                                ['label' => 'Sellers', 'path' => 'admin/sellers', 'icon' => '<path d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"></path>']
                            ],
                            'SYSTEM GOVERNANCE' => [
                                ['label' => 'Reports', 'path' => 'admin/reports', 'icon' => '<path d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>']
                            ],
                            'PRODUCT CONTROL' => [
                                ['label' => 'Products', 'path' => 'admin/products', 'icon' => '<path d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"></path>'],
                                ['label' => 'Categories', 'path' => 'admin/categories', 'icon' => '<path d="M4 6h16M4 10h16M4 14h16M4 18h16"></path>']
                            ]
                        ];
                    @endphp

                    @foreach($sidebarGroups as $group => $items)
                        <div class="space-y-4">
                            <div class="text-[9px] font-bold text-(--muted) opacity-60 tracking-widest uppercase px-3">{{ $group }}</div>
                            <div class="space-y-1.5">
                                @foreach($items as $item)
                                    <a href="/{{ $item['path'] }}" class="flex items-center gap-3 px-4 py-3.5 rounded-xl transition-all duration-300 group tracking-wide text-sm font-medium {{ request()->is($item['path'] . '*') ? 'bg-[rgba(192,66,42,0.08)] text-(--rust) border-l-4 border-(--rust)' : 'text-(--charcoal) hover:bg-(--cream) hover:text-(--rust)' }}">
                                        <svg class="w-5 h-5 {{ request()->is($item['path'] . '*') ? 'text-(--rust)' : 'text-(--muted) group-hover:text-(--rust)' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">{!! $item['icon'] !!}</svg>
                                        {{ $item['label'] }}
                                    </a>
                                @endforeach
                            </div>
                        </div>
                    @endforeach
                </nav>

                <div class="mt-10 pt-8 border-t border-(--border) shrink-0">
                    <div class="flex items-center gap-3 px-2 mb-6">
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
                            logout
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
                    <button class="w-10 h-10 flex items-center justify-center rounded-xl bg-(--cream) text-(--muted) hover:text-(--rust) transition-all">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"></path></svg>
                    </button>
                    <div class="text-sm font-bold text-(--muted) hidden sm:block">
                        {{ date('M d, Y') }}
                    </div>
                </div>
            </header>

            <!-- Page Content -->
            <main class="flex-1 overflow-y-auto p-4 lg:p-10 pb-24">
                <div class="max-w-[1200px] mx-auto">
                    {{-- Flash Messages --}}
                    @if(session('success'))
                        <div class="mb-6 px-5 py-4 bg-green-50 border border-green-100 rounded-2xl text-green-700 text-xs font-bold flex items-center gap-3">
                            <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                            {{ session('success') }}
                        </div>
                    @endif
                    @if(session('error'))
                        <div class="mb-6 px-5 py-4 bg-red-50 border border-red-100 rounded-2xl text-red-700 text-xs font-bold flex items-center gap-3">
                            <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                            {{ session('error') }}
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
</body>
</html>
