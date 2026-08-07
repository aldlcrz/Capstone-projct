<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ?? 'Super Admin' }} | LumBarong Governance</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=Playfair+Display:wght@700;800&display=swap" rel="stylesheet">
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
                    <a href="/superadmin/dashboard" class="font-serif text-lg font-black italic tracking-tight text-[#C0422A]">
                        LumBarong
                    </a>
                    <div class="flex items-center gap-1.5 mt-2 px-2 py-0.5 rounded-full bg-[#C0422A]/10 border border-[#C0422A]/20 text-[#C0422A] font-bold tracking-widest text-[9px] w-fit">
                        👑 SUPER ADMIN GOVERNANCE
                    </div>
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
            <header class="h-16 bg-white border-b border-[#E5DDD5] flex items-center justify-between px-6 lg:px-10 shrink-0 shadow-sm">
                <div class="flex items-center gap-3">
                    <h2 class="text-sm font-bold text-gray-500 uppercase tracking-widest">Super Admin Governance</h2>
                </div>
                <div class="text-xs text-gray-400 font-semibold">
                    {{ date('F d, Y') }}
                </div>
            </header>

            <!-- Content Area -->
            <main class="flex-1 overflow-y-auto p-6 lg:p-10">
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
        </div>
    </div>
    <x-confirmation-modal />
    @stack('scripts')
</body>
</html>
