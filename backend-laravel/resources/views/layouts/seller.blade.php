<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ?? 'Seller Dashboard' }} | LumBarong</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=Playfair+Display:wght@700;800&display=swap" rel="stylesheet">
    <script defer src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <style>
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
        <aside class="hidden lg:flex flex-col w-[280px] h-full bg-white border-r border-(--border) overflow-hidden">
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
            <!-- Top Header -->
            <header class="sticky top-0 z-40 bg-white border-b border-(--border) h-[72px] flex items-center shrink-0">
                <div class="w-full flex items-center justify-between px-4 lg:px-10">
                    <div class="lg:hidden">
                        <a href="/seller/dashboard" class="font-serif text-base font-bold text-(--charcoal) tracking-tighter">LUMBARONG</a>
                    </div>
                    <div class="hidden lg:flex flex-1"></div>
                    <div class="flex items-center gap-4">
                        <div class="text-right hidden sm:block">
                            <div class="text-sm font-bold text-(--charcoal)">{{ Auth::user()->name }}</div>
                            <div class="text-[10px] font-bold uppercase tracking-widest text-(--muted)">Verified Seller</div>
                        </div>
                        <div class="w-10 h-10 rounded-xl bg-(--charcoal) text-white flex items-center justify-center font-bold shadow-md">
                            {{ strtoupper(substr(Auth::user()->name, 0, 1)) }}
                        </div>
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

            <!-- Mobile Bottom Nav -->
            <nav class="lg:hidden fixed bottom-0 left-0 right-0 bg-white border-t border-(--border) h-[72px] flex items-center justify-around px-2 z-50">
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
            </nav>
        </div>
    </div>
    
    <x-confirmation-modal />
    @stack('scripts')
</body>
</html>
