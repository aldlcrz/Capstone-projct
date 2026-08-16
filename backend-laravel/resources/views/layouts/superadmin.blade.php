<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ?? 'Super Admin' }} | LumBarong Governance</title>

    <!-- Favicon -->
    <link rel="icon" type="image/x-icon" href="{{ asset('favicon.ico') }}">
    <link rel="apple-touch-icon" sizes="180x180" href="{{ asset('images/apple-touch-icon.png') }}">

    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=Playfair+Display:wght@700;800&display=swap" rel="stylesheet">
    <script defer src="https://unpkg.com/@alpinejs/collapse@3.x.x/dist/cdn.min.js"></script>
    <script defer src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <style>
        body { font-family: 'Inter', sans-serif; background-color: #F7F3EE; color: #1A1A1A; }
        .font-serif { font-family: 'Playfair Display', serif; }
        .no-scrollbar::-webkit-scrollbar { display: none; }
        .no-scrollbar { -ms-overflow-style: none; scrollbar-width: none; }
        [x-cloak] { display: none !important; }
    </style>
</head>
<body class="antialiased">
    <div x-data="{ isMobileMenuOpen: false }" class="flex h-screen overflow-hidden">

        <!-- Sidebar -->
        <aside class="hidden lg:flex flex-col w-70 h-full bg-white border-r border-[#E5DDD5] shrink-0 shadow-sm">
            <div class="p-8 flex flex-col h-full">
                <!-- App Brand -->
                <div class="mb-10 shrink-0">
                    <a href="/superadmin/dashboard" class="flex items-center gap-3 group">
                        <img src="{{ asset('images/logo-icon.png') }}" alt="LumBarong" class="w-9 h-9 object-contain rounded-full shadow-xs group-hover:scale-105 transition-transform">
                        <div>
                            <span class="font-serif text-lg font-black italic tracking-tight text-[#C0422A]">LumBarong</span>
                            <div class="flex items-center gap-1 px-1.5 py-0.5 rounded-full bg-[#C0422A]/10 border border-[#C0422A]/20 text-[#C0422A] font-bold tracking-widest text-[8px] w-fit mt-0.5">
                                👑 SUPER ADMIN
                            </div>
                        </div>
                    </a>
                </div>

                <!-- Navigation -->
                <nav class="flex-1 space-y-2 overflow-y-auto no-scrollbar">
                    <div class="text-[10px] font-bold text-gray-400 tracking-widest uppercase px-3 mb-3">Governance</div>

                    <a href="{{ route('superadmin.dashboard') }}"
                       class="flex items-center gap-3 px-4 py-3.5 rounded-xl transition-all font-medium text-sm {{ request()->routeIs('superadmin.dashboard') ? 'bg-[#C0422A]/10 text-[#C0422A] border border-[#C0422A]/20' : 'text-gray-500 hover:bg-[#F7F3EE] hover:text-[#3D2B1F]' }}">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                        </svg>
                        Dashboard Overview
                    </a>

                    <a href="{{ route('superadmin.commissions') }}"
                       class="flex items-center justify-between px-4 py-3.5 rounded-xl transition-all font-medium text-sm {{ request()->routeIs('superadmin.commissions') ? 'bg-[#C0422A]/10 text-[#C0422A] border border-[#C0422A]/20' : 'text-gray-500 hover:bg-[#F7F3EE] hover:text-[#3D2B1F]' }}">
                        <div class="flex items-center gap-3">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                            Commission &amp; Sales
                        </div>
                        @php
                            $unpaidBadge = \App\Models\CommissionRecord::where('status', 'unpaid')->count();
                        @endphp
                        @if($unpaidBadge > 0)
                            <span class="px-2 py-0.5 bg-red-50 text-red-500 border border-red-200 text-[10px] font-bold rounded-full">{{ $unpaidBadge }}</span>
                        @endif
                    </a>

                    <a href="{{ route('superadmin.payment-settings') }}"
                       class="flex items-center gap-3 px-4 py-3.5 rounded-xl transition-all font-medium text-sm {{ request()->routeIs('superadmin.payment-settings*') ? 'bg-[#C0422A]/10 text-[#C0422A] border border-[#C0422A]/20' : 'text-gray-500 hover:bg-[#F7F3EE] hover:text-[#3D2B1F]' }}">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z"></path>
                        </svg>
                        Payment Gateways
                    </a>
                </nav>

                <!-- User Footer -->
                <div class="mt-auto pt-6 border-t border-[#E5DDD5] shrink-0 space-y-4">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-xl bg-[#C0422A]/10 text-[#C0422A] border border-[#C0422A]/20 flex items-center justify-center font-bold">
                            👑
                        </div>
                        <div class="overflow-hidden">
                            <div class="text-sm font-bold text-[#3D2B1F] truncate">{{ Auth::user()->name }}</div>
                            <div class="text-[10px] text-[#C0422A] font-bold uppercase tracking-widest">Super Administrator</div>
                        </div>
                    </div>

                    <form x-ref="logoutForm" action="{{ route('superadmin.logout') }}" method="POST">
                        @csrf
                        <button type="button" 
                                @click="$dispatch('open-confirmation', { 
                                    title: 'Logout', 
                                    message: 'Are you sure you want to logout?', 
                                    confirmText: 'Logout', 
                                    type: 'danger', 
                                    onConfirm: () => $refs.logoutForm.submit() 
                                })" 
                                class="w-full py-3 bg-[#3D2B1F] text-white hover:bg-[#C0422A] rounded-xl text-xs font-bold uppercase tracking-widest transition-all">
                            Sign Out
                        </button>
                    </form>
                </div>
            </div>
        </aside>

        <!-- Main Body -->
        <div class="flex-1 flex flex-col h-full overflow-hidden">
            <!-- Header -->
            <header class="h-16 bg-white border-b border-[#E5DDD5] flex items-center justify-between px-4 lg:px-10 shrink-0 shadow-sm">
                <div class="flex items-center gap-3">
                    <a href="/superadmin/dashboard" class="lg:hidden flex items-center gap-2">
                        <img src="{{ asset('images/logo-icon.png') }}" alt="LumBarong" class="w-7 h-7 object-contain rounded-full shadow-xs">
                        <span class="font-serif text-base font-black italic tracking-tight text-[#C0422A]">LumBarong</span>
                    </a>
                    <h2 class="hidden sm:block text-xs sm:text-sm font-bold text-gray-500 uppercase tracking-widest">Super Admin Governance</h2>
                </div>
                
                <!-- Superadmin Profile Dropdown -->
                <div x-data="{ superProfileOpen: false }" class="relative" @click.away="superProfileOpen = false">
                    <button @click="superProfileOpen = !superProfileOpen" class="flex items-center gap-2 sm:gap-3 hover:opacity-80 transition-all cursor-pointer focus:outline-none">
                        <div class="text-right hidden sm:block">
                            <div class="text-xs font-bold text-[#3D2B1F] flex items-center gap-1 justify-end">
                                <span>{{ Auth::user()->name }}</span>
                                <span class="text-xs">👑</span>
                            </div>
                            <div class="text-[9px] text-[#C0422A] font-bold uppercase tracking-widest">Super Admin</div>
                        </div>
                        <div class="w-9 h-9 sm:w-10 sm:h-10 rounded-xl bg-[#C0422A]/10 text-[#C0422A] border border-[#C0422A]/20 flex items-center justify-center font-bold shadow-xs shrink-0">
                            👑
                        </div>
                    </button>

                    <div x-show="superProfileOpen"
                         x-transition:enter="transition ease-out duration-200"
                         x-transition:enter-start="opacity-0 translate-y-1 scale-95"
                         x-transition:enter-end="opacity-100 translate-y-0 scale-100"
                         x-transition:leave="transition ease-in duration-150"
                         x-transition:leave-start="opacity-100 translate-y-0 scale-100"
                         x-transition:leave-end="opacity-0 translate-y-1 scale-95"
                         class="absolute right-0 mt-2 w-56 bg-white rounded-2xl shadow-2xl border border-gray-100 z-50 overflow-hidden"
                         style="display: none;"
                         x-cloak>
                        <div class="px-4 py-3 bg-gray-50 border-b border-gray-100">
                            <div class="text-xs font-bold text-gray-900 truncate">{{ Auth::user()->name }}</div>
                            <div class="text-[9px] text-[#C0422A] font-bold uppercase tracking-wider">Super Administrator</div>
                        </div>
                        <div class="p-2">
                            <form x-ref="superDropdownLogoutForm" action="{{ route('superadmin.logout') }}" method="POST">
                                @csrf
                                <button type="button"
                                        @click="$dispatch('open-confirmation', {
                                            title: 'Logout',
                                            message: 'Are you sure you want to logout?',
                                            confirmText: 'Logout',
                                            type: 'danger',
                                            onConfirm: () => $refs.superDropdownLogoutForm.submit()
                                        })"
                                        class="w-full py-2 bg-[#3D2B1F] text-white hover:bg-[#C0422A] rounded-xl text-[10px] font-bold uppercase tracking-widest transition-all cursor-pointer">
                                    Sign Out
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </header>

            <!-- Content Area -->
            <main class="flex-1 overflow-y-auto p-4 lg:p-10 pb-24">
                <div class="max-w-7xl mx-auto space-y-8">
                    @if(session('success'))
                        <div class="p-4 rounded-2xl bg-green-50 border border-green-200 text-green-700 text-xs font-semibold flex items-center justify-between">
                            <span>✓ {{ session('success') }}</span>
                        </div>
                    @endif

                    @if(session('error'))
                        <div class="p-4 rounded-2xl bg-red-50 border border-red-200 text-red-600 text-xs font-semibold flex items-center justify-between">
                            <span>✕ {{ session('error') }}</span>
                        </div>
                    @endif

                    @yield('content')
                </div>
            </main>

            <!-- Superadmin Mobile Fixed Bottom Navigation Bar -->
            <nav class="lg:hidden fixed bottom-0 left-0 right-0 bg-white border-t border-gray-200/80 h-16 flex items-center justify-around px-2 z-40 shadow-lg">
                <a href="{{ route('superadmin.dashboard') }}" class="flex flex-col items-center gap-0.5 px-3 py-1 {{ request()->routeIs('superadmin.dashboard') ? 'text-[#C0422A]' : 'text-gray-500 hover:text-gray-700' }}">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path></svg>
                    <span class="text-[9px] font-semibold">Overview</span>
                </a>

                <a href="{{ route('superadmin.commissions') }}" class="flex flex-col items-center gap-0.5 px-3 py-1 {{ request()->routeIs('superadmin.commissions') ? 'text-[#C0422A]' : 'text-gray-500 hover:text-gray-700' }}">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                    <span class="text-[9px] font-semibold">Commissions</span>
                </a>

                <a href="{{ route('superadmin.payment-settings') }}" class="flex flex-col items-center gap-0.5 px-3 py-1 {{ request()->routeIs('superadmin.payment-settings*') ? 'text-[#C0422A]' : 'text-gray-500 hover:text-gray-700' }}">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M3 10h18M7 15h1m4 0h1m-7 4h12a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z"></path></svg>
                    <span class="text-[9px] font-semibold">Gateways</span>
                </a>
            </nav>
        </div>
    </div>
    <x-confirmation-modal />
    <x-modal-scroll-lock />
    @stack('scripts')
</body>
</html>
