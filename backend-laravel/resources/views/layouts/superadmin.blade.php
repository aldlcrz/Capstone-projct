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
                        '40': '40',
                        '50': '50',
                        '60': '60',
                        '70': '70',
                        '80': '80',
                        '9999': '9999',
                    }
                }
            }
        }
    </script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=Playfair+Display:wght@700;800&display=swap" rel="stylesheet">
    <script defer src="https://unpkg.com/@alpinejs/collapse@3.14.8/dist/cdn.min.js"></script>
    <script defer src="https://unpkg.com/alpinejs@3.14.8/dist/cdn.min.js"></script>
    <style>
        :root {
            --rust: #C0420A;
            --cream: #F8F7F4;
            --charcoal: #1F2937;
            --muted: #4B5563;
            --border: #E5E7EB;
        }
        body { font-family: 'Inter', sans-serif; background-color: #F7F3EE; color: #1F2937; }
        .font-serif { font-family: 'Playfair Display', serif; }
        .no-scrollbar::-webkit-scrollbar { display: none; }
        .no-scrollbar { -ms-overflow-style: none; scrollbar-width: none; }
        [x-cloak] { display: none !important; }
    </style>
</head>
<body class="antialiased text-[#1F2937]">
    <div x-data="{ isMobileMenuOpen: false }" class="flex h-screen overflow-hidden">

        <!-- Desktop Sidebar -->
        <aside class="hidden lg:flex flex-col w-70 h-full bg-white border-r border-gray-200 shrink-0 overflow-hidden shadow-xs">
            <div class="p-8 flex flex-col h-full">
                <!-- App Brand -->
                <div class="mb-10 shrink-0">
                    <a href="/superadmin/dashboard" class="flex items-center gap-3 group">
                        <img src="{{ asset('images/logo-icon.png') }}" alt="LumBarong Logo" class="w-9 h-9 object-contain rounded-full shadow-xs group-hover:scale-105 transition-transform">
                        <div>
                            <span class="font-serif text-lg font-bold text-[#1F2937] tracking-tight">LUMBARONG</span>
                            <div class="flex items-center gap-1.5 px-0.5 text-[#C0420A] font-bold tracking-widest text-[9px] uppercase">
                                <span>👑 SUPER ADMIN</span>
                            </div>
                        </div>
                    </a>
                </div>

                <!-- Navigation -->
                <nav class="flex-1 space-y-6 overflow-y-auto no-scrollbar">
                    @php
                        $unpaidCommissionsCount = \App\Models\CommissionRecord::where('status', 'unpaid')->count();
                        $pendingSubsCount = \App\Models\SellerSubscription::where('status', 'pending')->count();
                        $pendingProductsCount = \App\Models\Product::where('status', 'pending')->count();
                        $pendingBannersCount = \App\Models\Banner::whereNotNull('userId')->where('status', 'pending')->count();

                        $sidebarGroups = [
                            'GOVERNANCE & FINANCE' => [
                                [
                                    'label' => 'Dashboard Overview',
                                    'route' => 'superadmin.dashboard',
                                    'path'  => 'superadmin/dashboard',
                                    'icon'  => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>'
                                ],
                                [
                                    'label' => 'Profit & Commissions',
                                    'route' => 'superadmin.commissions',
                                    'path'  => 'superadmin/commissions',
                                    'icon'  => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>',
                                    'badge' => $unpaidCommissionsCount
                                ],
                                [
                                    'label' => 'Payment Gateways',
                                    'route' => 'superadmin.payment-settings',
                                    'path'  => 'superadmin/payment-settings',
                                    'icon'  => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z"></path>'
                                ],
                                [
                                    'label' => 'Subscription Tiers',
                                    'route' => 'superadmin.subscriptions.index',
                                    'path'  => 'superadmin/subscriptions',
                                    'icon'  => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4M7.835 4.697a3.42 3.42 0 001.946-.806 3.42 3.42 0 014.438 0 3.42 3.42 0 001.946.806 3.42 3.42 0 013.138 3.138 3.42 3.42 0 00.806 1.946 3.42 3.42 0 010 4.438 3.42 3.42 0 00-.806 1.946 3.42 3.42 0 01-3.138 3.138 3.42 3.42 0 00-1.946.806 3.42 3.42 0 01-4.438 0 3.42 3.42 0 00-1.946-.806 3.42 3.42 0 01-3.138-3.138 3.42 3.42 0 00-.806-1.946 3.42 3.42 0 010-4.438 3.42 3.42 0 00.806-1.946 3.42 3.42 0 013.138-3.138z"></path>',
                                    'badge' => $pendingSubsCount
                                ],
                            ],
                            'CATALOG & CONTENT' => [
                                [
                                    'label' => 'Product Categories',
                                    'route' => 'superadmin.categories.index',
                                    'path'  => 'superadmin/categories',
                                    'icon'  => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 10h16M4 14h16M4 18h16"></path>'
                                ],
                                [
                                    'label' => 'Product Moderation',
                                    'route' => 'superadmin.products',
                                    'path'  => 'superadmin/products',
                                    'icon'  => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"></path>',
                                    'badge' => $pendingProductsCount
                                ],
                                [
                                    'label' => 'Promotions & Banners',
                                    'route' => 'superadmin.banners.index',
                                    'path'  => 'superadmin/banners',
                                    'icon'  => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path>',
                                    'badge' => $pendingBannersCount
                                ],
                            ],
                            'USER REGISTRY' => [
                                [
                                    'label' => 'Sellers & Shops',
                                    'route' => 'superadmin.sellers',
                                    'path'  => 'superadmin/sellers',
                                    'icon'  => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"></path>'
                                ],
                                [
                                    'label' => 'Customer Directory',
                                    'route' => 'superadmin.customers',
                                    'path'  => 'superadmin/customers',
                                    'icon'  => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path>'
                                ],
                            ],
                            'DEVELOPER & SYSTEM' => [
                                [
                                    'label' => 'Archive Vault',
                                    'route' => 'superadmin.archives',
                                    'path'  => 'superadmin/archives',
                                    'icon'  => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4"></path>'
                                ],
                                [
                                    'label' => 'System Maintenance',
                                    'route' => 'superadmin.maintenance',
                                    'path'  => 'superadmin/maintenance',
                                    'icon'  => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z"></path>'
                                ],
                                [
                                    'label' => 'Audit & Security Logs',
                                    'route' => 'superadmin.audit-logs',
                                    'path'  => 'superadmin/audit-logs',
                                    'icon'  => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"></path>'
                                ],
                                [
                                    'label' => 'System Error Logs',
                                    'route' => 'superadmin.error-logs',
                                    'path'  => 'superadmin/error-logs',
                                    'icon'  => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>'
                                ],
                            ]
                        ];
                    @endphp

                    @foreach($sidebarGroups as $group => $items)
                        @if(!$loop->first)
                            <div class="border-t border-gray-200"></div>
                        @endif
                        <div class="space-y-1">
                            <div class="text-[10px] font-black text-gray-500 tracking-widest uppercase px-3 mb-2">{{ $group }}</div>
                            @foreach($items as $item)
                                @php
                                    $isActive = request()->is($item['path'] . '*');
                                @endphp
                                <a href="/{{ $item['path'] }}"
                                   class="flex items-center justify-between px-4 py-3.5 rounded-xl transition-all duration-300 group tracking-wide text-sm font-medium {{ $isActive ? 'bg-[rgba(192,66,42,0.08)] text-[#C0420A] border-l-4 border-[#C0420A] font-bold' : 'text-[#1F2937] hover:bg-[#F8F7F4] hover:text-[#C0420A]' }}">
                                    <div class="flex items-center gap-3">
                                        <svg class="w-5 h-5 {{ $isActive ? 'text-[#C0422A]' : 'text-gray-500 group-hover:text-[#C0420A]' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">{!! $item['icon'] !!}</svg>
                                        <span>{{ $item['label'] }}</span>
                                    </div>
                                    @if(isset($item['badge']) && $item['badge'] > 0)
                                        <span class="px-2 py-0.5 bg-red-500 text-white text-[9px] font-bold rounded-full border border-white shrink-0">{{ $item['badge'] }}</span>
                                    @endif
                                </a>
                            @endforeach
                        </div>
                    @endforeach
                </nav>

                <!-- User Footer Card -->
                <div class="mt-6 pt-6 border-t border-gray-200 shrink-0 space-y-3">
                    <div class="flex items-center gap-3 px-2">
                        <div class="w-10 h-10 rounded-xl bg-[#1F2937] text-amber-400 flex items-center justify-center font-bold text-sm shadow-xs border border-amber-500/20">
                            👑
                        </div>
                        <div class="overflow-hidden">
                            <div class="text-sm font-bold text-[#1F2937] truncate">{{ Auth::user()->name }}</div>
                            <div class="text-[10px] text-[#C0420A] font-bold uppercase tracking-widest leading-none">Super Admin</div>
                        </div>
                    </div>

                    <form x-ref="logoutForm" action="{{ route('superadmin.logout') }}" method="POST">
                        @csrf
                        <button type="button" 
                                @click="$dispatch('open-confirmation', { 
                                     title: 'Sign Out', 
                                     message: 'Are you sure you want to sign out of Super Admin Governance?', 
                                     confirmText: 'Sign Out', 
                                     type: 'danger', 
                                     onConfirm: () => $refs.logoutForm.submit() 
                                })" 
                                class="flex items-center gap-3 w-full px-4 py-3.5 bg-red-50 text-red-600 rounded-xl hover:bg-red-100 transition-all font-bold text-xs tracking-widest uppercase shadow-none border-0 cursor-pointer">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"></path></svg>
                            <span>Sign Out</span>
                        </button>
                    </form>
                </div>
            </div>
        </aside>

        <!-- Main Content Area -->
        <div class="flex-1 flex flex-col h-full relative overflow-hidden">
            <!-- Header -->
            <header class="sticky top-0 z-40 bg-white border-b border-gray-200 h-16 lg:h-18 flex items-center shrink-0 px-4 lg:px-10 justify-between">
                <div class="flex items-center gap-2">
                    <a href="/superadmin/dashboard" class="lg:hidden flex items-center gap-2">
                        <img src="{{ asset('images/logo-icon.png') }}" alt="LumBarong" class="w-7 h-7 object-contain rounded-full shadow-xs">
                        <span class="font-serif font-bold text-[#1F2937] tracking-tight text-base">LUMBARONG</span>
                        <span class="text-[9px] font-black text-[#C0420A] px-1.5 py-0.5 bg-[#C0420A]/10 rounded uppercase">Super Admin</span>
                    </a>
                    <div class="hidden lg:flex items-center gap-2">
                        <span class="text-[9px] font-black uppercase tracking-[0.25em] text-[#C0422A]">Platform Governance</span>
                        <span class="text-gray-300 text-xs">·</span>
                        <span class="text-[9px] font-bold uppercase tracking-[0.2em] text-gray-400">Supreme Control</span>
                    </div>
                </div>

                <div class="flex items-center gap-4">
                    <!-- Super Admin Profile Dropdown -->
                    <div x-data="{ superProfileOpen: false }" class="relative" @click.away="superProfileOpen = false">
                        <button @click="superProfileOpen = !superProfileOpen" class="flex items-center gap-3 hover:opacity-80 transition-all cursor-pointer focus:outline-none" title="Super Admin Profile">
                            <div class="text-right hidden sm:block">
                                <div class="text-sm font-bold text-gray-900 flex items-center gap-1.5 justify-end">
                                    {{ Auth::user()->name }}
                                    <span class="text-xs">👑</span>
                                </div>
                                <div class="text-[10px] font-bold uppercase tracking-widest text-[#C0420A]">
                                    Super Administrator
                                </div>
                            </div>
                            <div class="w-9 h-9 sm:w-10 sm:h-10 rounded-xl bg-black text-amber-400 flex items-center justify-center font-bold shadow-md overflow-hidden shrink-0 border-2 border-white">
                                👑
                            </div>
                            <svg class="w-4 h-4 text-gray-400 hidden sm:block transition-transform duration-200" :class="{ 'rotate-180': superProfileOpen }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                            </svg>
                        </button>

                        <!-- Dropdown Menu -->
                        <div x-show="superProfileOpen"
                             x-transition:enter="transition ease-out duration-200"
                             x-transition:enter-start="opacity-0 translate-y-1 scale-95"
                             x-transition:enter-end="opacity-100 translate-y-0 scale-100"
                             x-transition:leave="transition ease-in duration-150"
                             x-transition:leave-start="opacity-100 translate-y-0 scale-100"
                             x-transition:leave-end="opacity-0 translate-y-1 scale-95"
                             class="absolute right-0 mt-2 w-64 bg-white rounded-2xl shadow-2xl border border-gray-100 z-50 overflow-hidden"
                             style="display: none;"
                             x-cloak>
                            
                            <div class="px-4 py-3.5 bg-gray-50 border-b border-gray-100 flex items-center gap-3">
                                <div class="w-9 h-9 rounded-xl bg-black text-amber-400 flex items-center justify-center font-bold text-xs shrink-0">
                                    👑
                                </div>
                                <div class="min-w-0">
                                    <div class="text-xs font-bold text-gray-900 truncate">{{ Auth::user()->name }}</div>
                                    <div class="text-[10px] text-gray-400 truncate">{{ Auth::user()->email }}</div>
                                </div>
                            </div>

                            <div class="p-2 border-t border-gray-100 bg-gray-50">
                                <form x-ref="superDropdownLogoutForm" action="{{ route('superadmin.logout') }}" method="POST">
                                    @csrf
                                    <button type="button"
                                            @click="$dispatch('open-confirmation', {
                                                title: 'Sign Out',
                                                message: 'Are you sure you want to sign out of Super Admin?',
                                                confirmText: 'Sign Out',
                                                type: 'danger',
                                                onConfirm: () => $refs.superDropdownLogoutForm.submit()
                                            })"
                                            class="flex items-center justify-center gap-2 w-full py-2 bg-red-50 text-red-600 rounded-xl hover:bg-red-100 transition-all font-bold text-[10px] tracking-widest uppercase cursor-pointer shadow-none border-0">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"></path></svg>
                                        Sign Out
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </header>

            <!-- Page Content -->
            <main id="superadmin-main" class="flex-1 overflow-y-auto p-4 lg:p-10 pb-24">
                <div class="max-w-300 mx-auto">
                    {{-- Floating Toast Notifications --}}
                    <div 
                        x-data="{ 
                            toasts: [],
                            init() {
                                @if(session('success'))
                                    this.addToast('{{ addslashes(session('success')) }}', 'success');
                                @endif
                                @if(session('error'))
                                    this.addToast('{{ addslashes(session('error')) }}', 'error');
                                @endif
                                @if(session('warning'))
                                    this.addToast('{{ addslashes(session('warning')) }}', 'warning');
                                @endif
                                @if(session('info'))
                                    this.addToast('{{ addslashes(session('info')) }}', 'info');
                                @endif
                            },
                            addToast(message, type = 'success') {
                                const id = Date.now() + Math.random();
                                this.toasts.push({ id, message, type, show: true });
                                setTimeout(() => this.removeToast(id), 4500);
                            },
                            removeToast(id) {
                                const t = this.toasts.find(x => x.id === id);
                                if (t) t.show = false;
                                setTimeout(() => {
                                    this.toasts = this.toasts.filter(x => x.id !== id);
                                }, 300);
                            }
                        }"
                        @admin-toast.window="addToast($event.detail.message, $event.detail.type || 'success')"
                        class="fixed top-5 right-4 sm:right-8 z-9999 flex flex-col gap-2.5 max-w-sm w-[calc(100%-2rem)] pointer-events-none"
                    >
                        <template x-for="toast in toasts" :key="toast.id">
                            <div 
                                x-show="toast.show"
                                x-transition:enter="transition ease-out duration-300 transform"
                                x-transition:enter-start="opacity-0 translate-y-2 scale-95"
                                x-transition:enter-end="opacity-100 translate-y-0 scale-100"
                                x-transition:leave="transition ease-in duration-200 transform"
                                x-transition:leave-start="opacity-100 translate-y-0 scale-100"
                                x-transition:leave-end="opacity-0 translate-y-2 scale-95"
                                class="pointer-events-auto bg-white/95 backdrop-blur-md rounded-2xl shadow-2xl border border-gray-100 p-4 flex items-start gap-3.5 relative overflow-hidden"
                            >
                                <template x-if="toast.type === 'success'">
                                    <div class="w-8 h-8 rounded-xl bg-emerald-50 flex items-center justify-center text-emerald-600 shrink-0 shadow-sm border border-emerald-100">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"></path></svg>
                                    </div>
                                </template>
                                <template x-if="toast.type === 'error'">
                                    <div class="w-8 h-8 rounded-xl bg-rose-50 flex items-center justify-center text-rose-600 shrink-0 shadow-sm border border-rose-100">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12"></path></svg>
                                    </div>
                                </template>
                                <template x-if="toast.type === 'warning'">
                                    <div class="w-8 h-8 rounded-xl bg-amber-50 flex items-center justify-center text-amber-600 shrink-0 shadow-sm border border-amber-100">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
                                    </div>
                                </template>
                                <template x-if="toast.type === 'info'">
                                    <div class="w-8 h-8 rounded-xl bg-blue-50 flex items-center justify-center text-blue-600 shrink-0 shadow-sm border border-blue-100">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                                    </div>
                                </template>

                                <div class="grow pt-0.5">
                                    <h4 class="text-[11px] font-black text-black uppercase tracking-wider" x-text="toast.type === 'success' ? 'Success' : (toast.type === 'error' ? 'Error' : (toast.type === 'warning' ? 'Notice' : 'Info'))"></h4>
                                    <p class="text-xs text-gray-600 font-medium mt-0.5 leading-relaxed" x-text="toast.message"></p>
                                </div>

                                <button type="button" @click="removeToast(toast.id)" class="text-gray-300 hover:text-gray-500 transition-colors shrink-0 cursor-pointer">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                                </button>
                            </div>
                        </template>
                    </div>

                    @yield('content')
                </div>
            </main>

            <!-- Superadmin Mobile Fixed Bottom Navigation Bar -->
            <nav class="lg:hidden fixed bottom-0 left-0 right-0 bg-white border-t border-gray-200/80 h-16 flex items-center justify-around px-2 z-40 shadow-lg">
                <!-- Overview -->
                <a href="{{ route('superadmin.dashboard') }}" class="flex flex-col items-center gap-0.5 px-2.5 py-1 {{ request()->routeIs('superadmin.dashboard') ? 'text-[#C0420A]' : 'text-gray-500 hover:text-gray-700' }}">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M3 12l9-9 9 9M5 10v10a1 1 0 001 1h3a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1h3a1 1 0 001-1V10"></path>
                    </svg>
                    <span class="text-[9px] font-semibold">Overview</span>
                </a>

                <!-- Commissions -->
                <a href="{{ route('superadmin.commissions') }}" class="flex flex-col items-center gap-0.5 px-2.5 py-1 {{ request()->routeIs('superadmin.commissions*') ? 'text-[#C0420A]' : 'text-gray-500 hover:text-gray-700' }}">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                    <span class="text-[9px] font-semibold">Commissions</span>
                </a>

                <!-- Sellers -->
                <a href="{{ route('superadmin.sellers') }}" class="flex flex-col items-center gap-0.5 px-2.5 py-1 {{ request()->routeIs('superadmin.sellers*') ? 'text-[#C0420A]' : 'text-gray-500 hover:text-gray-700' }}">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"></path>
                    </svg>
                    <span class="text-[9px] font-semibold">Sellers</span>
                </a>

                <!-- Products -->
                <a href="{{ route('superadmin.products') }}" class="flex flex-col items-center gap-0.5 px-2.5 py-1 {{ request()->routeIs('superadmin.products*') ? 'text-[#C0420A]' : 'text-gray-500 hover:text-gray-700' }}">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"></path>
                    </svg>
                    <span class="text-[9px] font-semibold">Products</span>
                </a>

                <!-- More Sheet -->
                <div x-data="{ mobileMoreOpen: false }" class="relative">
                    <button @click="mobileMoreOpen = !mobileMoreOpen" class="flex flex-col items-center gap-0.5 px-2.5 py-1 text-gray-500 hover:text-gray-700 cursor-pointer">
                        <div class="w-6 h-6 rounded-full bg-black text-amber-400 flex items-center justify-center text-[10px] font-bold overflow-hidden border border-white">
                            👑
                        </div>
                        <span class="text-[9px] font-semibold">More ▾</span>
                    </button>

                    <!-- Popup Sheet -->
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
                            <div class="text-xs font-bold text-gray-900 truncate">{{ Auth::user()->name }}</div>
                            <div class="text-[9px] font-bold text-[#C0420A] uppercase tracking-wider">Super Administrator</div>
                        </div>

                        <div class="py-1">
                            <a href="{{ route('superadmin.payment-settings') }}" class="flex items-center gap-2.5 px-4 py-2 text-xs font-semibold text-gray-700 hover:bg-gray-50">
                                <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z"></path></svg>
                                <span>Gateways</span>
                            </a>
                            <a href="{{ route('superadmin.subscriptions.index') }}" class="flex items-center gap-2.5 px-4 py-2 text-xs font-semibold text-gray-700 hover:bg-gray-50">
                                <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4M7.835 4.697a3.42 3.42 0 001.946-.806 3.42 3.42 0 014.438 0 3.42 3.42 0 001.946.806 3.42 3.42 0 013.138 3.138 3.42 3.42 0 00.806 1.946 3.42 3.42 0 010 4.438 3.42 3.42 0 00-.806 1.946 3.42 3.42 0 01-3.138 3.138 3.42 3.42 0 00-1.946.806 3.42 3.42 0 01-4.438 0 3.42 3.42 0 00-1.946-.806 3.42 3.42 0 01-3.138-3.138 3.42 3.42 0 00-.806-1.946 3.42 3.42 0 010-4.438 3.42 3.42 0 00.806-1.946 3.42 3.42 0 013.138-3.138z"></path></svg>
                                <span>Subscriptions</span>
                            </a>
                            <a href="{{ route('superadmin.categories.index') }}" class="flex items-center gap-2.5 px-4 py-2 text-xs font-semibold text-gray-700 hover:bg-gray-50">
                                <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 10h16M4 14h16M4 18h16"></path></svg>
                                <span>Categories</span>
                            </a>
                            <a href="{{ route('superadmin.banners.index') }}" class="flex items-center gap-2.5 px-4 py-2 text-xs font-semibold text-gray-700 hover:bg-gray-50">
                                <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                                <span>Promotions</span>
                            </a>
                            <a href="{{ route('superadmin.archives') }}" class="flex items-center gap-2.5 px-4 py-2 text-xs font-semibold text-gray-700 hover:bg-gray-50">
                                <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4"></path></svg>
                                <span>Archives</span>
                            </a>
                            <a href="{{ route('superadmin.maintenance') }}" class="flex items-center gap-2.5 px-4 py-2 text-xs font-semibold text-gray-700 hover:bg-gray-50">
                                <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z"></path></svg>
                                <span>Maintenance</span>
                            </a>
                            <a href="{{ route('superadmin.audit-logs') }}" class="flex items-center gap-2.5 px-4 py-2 text-xs font-semibold text-gray-700 hover:bg-gray-50">
                                <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"></path></svg>
                                <span>Audit Logs</span>
                            </a>
                            <a href="{{ route('superadmin.error-logs') }}" class="flex items-center gap-2.5 px-4 py-2 text-xs font-semibold text-gray-700 hover:bg-gray-50">
                                <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
                                <span>Error Logs</span>
                            </a>
                        </div>

                        <div class="p-2 border-t border-gray-100 bg-gray-50">
                            <form x-ref="superMobileBottomLogoutForm" action="{{ route('superadmin.logout') }}" method="POST">
                                @csrf
                                <button type="button"
                                        @click="$dispatch('open-confirmation', {
                                            title: 'Sign Out',
                                            message: 'Are you sure you want to sign out of Super Admin?',
                                            confirmText: 'Sign Out',
                                            type: 'danger',
                                            onConfirm: () => $refs.superMobileBottomLogoutForm.submit()
                                        })"
                                        class="flex items-center justify-center gap-2 w-full py-2 bg-red-50 text-red-600 rounded-xl font-bold text-[10px] tracking-widest uppercase cursor-pointer shadow-none border-0 hover:bg-red-100 transition-all">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"></path></svg>
                                    Sign Out
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </nav>
        </div>
    </div>
    
    <x-confirmation-modal />
    <x-modal-scroll-lock />

    @stack('scripts')
</body>
</html>
