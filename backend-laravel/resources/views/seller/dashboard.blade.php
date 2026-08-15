@extends('layouts.seller')

@section('content')
<div class="space-y-6 sm:space-y-8">
    {{-- Header & Date Filter Toolbar --}}
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            @php
                $userRole = auth()->user()->role ?? 'seller';
                $roleLabel = match(strtolower($userRole)) {
                    'superadmin' => 'SuperAdmin',
                    'admin' => 'Admin',
                    default => 'Seller',
                };
            @endphp
            <div class="text-xs font-bold text-[#C0422A] uppercase tracking-[0.15em] mb-1">Welcome Back, {{ $roleLabel }} {{ auth()->user()->name }}</div>
            <div class="text-[10px] font-bold text-gray-400 uppercase tracking-[0.2em] mb-1">Seller Performance Overview</div>
            <h1 class="font-serif text-2xl sm:text-3xl font-bold text-black uppercase">Seller <span class="text-[#C0422A] italic lowercase">dashboard</span></h1>
        </div>
    </div>

    {{-- Filter Toolbar --}}
    <form method="GET" action="{{ route('seller.dashboard') }}" x-data="{ selectedPreset: '{{ $filters['preset'] ?? 'all_time' }}' }" class="flex items-center justify-between sm:justify-start gap-3">
        <div class="flex-1 sm:flex-initial sm:w-64 relative flex items-center bg-white border border-gray-200/90 rounded-2xl px-4 py-2.5 shadow-xs hover:border-gray-300 transition-all">
            <svg class="w-5 h-5 text-gray-400 shrink-0 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
            </svg>
            <select name="date_preset" x-model="selectedPreset" @change="$el.form.submit()" class="w-full bg-transparent text-xs sm:text-sm font-semibold text-gray-800 outline-none cursor-pointer appearance-none pr-6">
                <option value="all_time" {{ in_array(($filters['preset'] ?? ''), ['all_time', '']) ? 'selected' : '' }}>All Time</option>
                <option value="today" {{ ($filters['preset'] ?? '') == 'today' ? 'selected' : '' }}>Today</option>
                <option value="1_week" {{ in_array(($filters['preset'] ?? ''), ['1_week', 'last_7_days']) ? 'selected' : '' }}>1 Week</option>
                <option value="1_month" {{ in_array(($filters['preset'] ?? ''), ['1_month', 'last_30_days', 'this_month']) ? 'selected' : '' }}>1 Month</option>
                <option value="1_year" {{ in_array(($filters['preset'] ?? ''), ['1_year', 'last_365_days']) ? 'selected' : '' }}>1 Year</option>
            </select>
            <svg class="w-4 h-4 text-gray-400 absolute right-4 pointer-events-none" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
            </svg>
        </div>

        @if(($filters['preset'] ?? 'all_time') !== 'all_time')
            <a href="{{ route('seller.dashboard') }}" class="text-[10px] font-bold uppercase tracking-widest text-gray-400 hover:text-red-600 transition-colors shrink-0 px-1">
                Reset ✕
            </a>
        @endif

        {{-- Export Button --}}
        <a href="{{ route('seller.export', request()->all()) }}" class="flex items-center gap-2 px-5 py-2.5 bg-[#C0422A] text-white rounded-2xl text-xs font-bold shrink-0 hover:bg-[#a63721] transition-all shadow-sm">
            <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path>
            </svg>
            <span>Export</span>
        </a>
    </form>

    {{-- SECTION 1: QUICK ALERTS --}}
    <div class="space-y-3">
        <div class="flex items-center justify-between">
            <h2 class="text-xs font-black uppercase tracking-widest text-gray-400 flex items-center gap-2">
                <span class="w-2 h-2 rounded-full bg-red-500 animate-pulse"></span>
                Quick Alerts & Notifications
            </h2>
        </div>

        <div class="grid grid-cols-2 lg:grid-cols-4 gap-2.5 sm:gap-4">
            <!-- New Orders Alert -->
            <a href="{{ route('seller.orders') }}" class="bg-white p-3.5 sm:p-5 rounded-2xl border border-gray-100 shadow-xs hover:border-blue-300 hover:shadow-md transition-all block group">
                <div class="flex items-center justify-between mb-2">
                    <div class="w-8 h-8 rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center shrink-0">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/></svg>
                    </div>
                    <span class="text-[9px] font-black uppercase px-2 py-0.5 rounded-full {{ ($quickAlerts['newOrders'] ?? 0) > 0 ? 'bg-blue-100 text-blue-700' : 'bg-gray-100 text-gray-400' }}">New</span>
                </div>
                <div class="text-lg sm:text-2xl font-black text-black group-hover:text-blue-600 transition-colors">{{ $quickAlerts['newOrders'] ?? 0 }}</div>
                <div class="text-[9px] font-bold text-gray-400 uppercase tracking-wider mt-0.5">New Orders (24h)</div>
            </a>

            <!-- Low Stock Alert -->
            <a href="{{ route('seller.products.index') }}" class="bg-white p-3.5 sm:p-5 rounded-2xl border border-gray-100 shadow-xs hover:border-amber-300 hover:shadow-md transition-all block group">
                <div class="flex items-center justify-between mb-2">
                    <div class="w-8 h-8 rounded-xl bg-amber-50 text-amber-600 flex items-center justify-center shrink-0">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                    </div>
                    <span class="text-[9px] font-black uppercase px-2 py-0.5 rounded-full {{ ($quickAlerts['lowStock'] ?? 0) > 0 ? 'bg-amber-100 text-amber-700' : 'bg-gray-100 text-gray-400' }}">Low Stock</span>
                </div>
                <div class="text-lg sm:text-2xl font-black text-black group-hover:text-amber-600 transition-colors">{{ $quickAlerts['lowStock'] ?? 0 }}</div>
                <div class="text-[9px] font-bold text-gray-400 uppercase tracking-wider mt-0.5">Items Need Restock</div>
            </a>

            <!-- New Reviews Alert -->
            <a href="{{ route('seller.profile') }}" class="bg-white p-3.5 sm:p-5 rounded-2xl border border-gray-100 shadow-xs hover:border-emerald-300 hover:shadow-md transition-all block group">
                <div class="flex items-center justify-between mb-2">
                    <div class="w-8 h-8 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center shrink-0">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z"/></svg>
                    </div>
                    <span class="text-[9px] font-black uppercase px-2 py-0.5 rounded-full bg-emerald-100 text-emerald-700">7 Days</span>
                </div>
                <div class="text-lg sm:text-2xl font-black text-black group-hover:text-emerald-600 transition-colors">{{ $quickAlerts['newReviews'] ?? 0 }}</div>
                <div class="text-[9px] font-bold text-gray-400 uppercase tracking-wider mt-0.5">New Reviews</div>
            </a>

            <!-- Messages Alert -->
            <a href="{{ route('seller.messages') }}" class="bg-white p-3.5 sm:p-5 rounded-2xl border border-gray-100 shadow-xs hover:border-purple-300 hover:shadow-md transition-all block group">
                <div class="flex items-center justify-between mb-2">
                    <div class="w-8 h-8 rounded-xl bg-purple-50 text-purple-600 flex items-center justify-center shrink-0">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/></svg>
                    </div>
                    <span class="text-[9px] font-black uppercase px-2 py-0.5 rounded-full {{ ($quickAlerts['messages'] ?? 0) > 0 ? 'bg-purple-100 text-purple-700' : 'bg-gray-100 text-gray-400' }}">Inbox</span>
                </div>
                <div class="text-lg sm:text-2xl font-black text-black group-hover:text-purple-600 transition-colors">{{ $quickAlerts['messages'] ?? 0 }}</div>
                <div class="text-[9px] font-bold text-gray-400 uppercase tracking-wider mt-0.5">Unread Messages</div>
            </a>
        </div>
    </div>

    {{-- SECTION 2: SALES SUMMARY --}}
    <div class="space-y-4">
        <div class="flex items-center justify-between">
            <h2 class="text-xs font-black uppercase tracking-widest text-gray-400">Sales Summary</h2>
        </div>

        {{-- Sales Timeline Cards (Today, Weekly, Monthly, Total Revenue) --}}
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-3 sm:gap-6">
            <!-- Today's Sales -->
            <div class="bg-white p-4 sm:p-6 rounded-2xl border border-gray-100 shadow-sm relative overflow-hidden">
                <div class="text-[9px] sm:text-[10px] font-bold uppercase tracking-widest text-gray-400 mb-1">Today's Sales</div>
                <div class="text-base sm:text-2xl font-black text-[#C0422A]">₱{{ number_format($salesSummary['todaySales'] ?? 0, 2) }}</div>
                <div class="text-[9px] font-bold text-gray-400 mt-1 uppercase tracking-wider">Start of day</div>
            </div>

            <!-- Weekly Sales -->
            <div class="bg-white p-4 sm:p-6 rounded-2xl border border-gray-100 shadow-sm relative overflow-hidden">
                <div class="text-[9px] sm:text-[10px] font-bold uppercase tracking-widest text-gray-400 mb-1">Weekly Sales</div>
                <div class="text-base sm:text-2xl font-black text-black">₱{{ number_format($salesSummary['weeklySales'] ?? 0, 2) }}</div>
                <div class="text-[9px] font-bold text-gray-400 mt-1 uppercase tracking-wider">Last 7 Days</div>
            </div>

            <!-- Monthly Sales -->
            <div class="bg-white p-4 sm:p-6 rounded-2xl border border-gray-100 shadow-sm relative overflow-hidden">
                <div class="text-[9px] sm:text-[10px] font-bold uppercase tracking-widest text-gray-400 mb-1">Monthly Sales</div>
                <div class="text-base sm:text-2xl font-black text-black">₱{{ number_format($salesSummary['monthlySales'] ?? 0, 2) }}</div>
                <div class="text-[9px] font-bold text-gray-400 mt-1 uppercase tracking-wider">Last 30 Days</div>
            </div>

            <!-- Total Revenue -->
            <div class="bg-white p-4 sm:p-6 rounded-2xl border border-gray-100 shadow-sm relative overflow-hidden">
                <div class="text-[9px] sm:text-[10px] font-bold uppercase tracking-widest text-gray-400 mb-1">Total Revenue</div>
                <div class="text-base sm:text-2xl font-black text-black">₱{{ number_format($salesSummary['totalRevenue'] ?? 0, 2) }}</div>
                <div class="text-[9px] font-bold text-gray-400 mt-1 uppercase tracking-wider">{{ $salesSummary['totalOrders'] ?? 0 }} Total Orders</div>
            </div>
        </div>

        {{-- Order Pipeline Status Grid --}}
        <div class="bg-white p-4 sm:p-6 rounded-2xl sm:rounded-3xl border border-gray-100 shadow-sm space-y-3">
            <div class="text-xs font-black uppercase tracking-widest text-black flex items-center justify-between">
                <span>Order Pipeline Status</span>
                <a href="{{ route('seller.orders') }}" class="text-[10px] font-bold text-[#C0422A] hover:underline">View All Orders &rarr;</a>
            </div>

            <div class="grid grid-cols-2 sm:grid-cols-4 gap-2.5 sm:gap-4">
                <a href="{{ route('seller.orders') }}" class="p-3.5 bg-amber-50/60 rounded-xl border border-amber-100 hover:border-amber-300 transition-all flex items-center gap-3">
                    <div class="w-8 h-8 rounded-lg bg-amber-100 text-amber-700 flex items-center justify-center font-black text-sm shrink-0">{{ $salesSummary['pendingOrders'] ?? 0 }}</div>
                    <div class="min-w-0">
                        <div class="text-xs font-bold text-black truncate">Pending</div>
                        <div class="text-[9px] text-amber-700 font-bold uppercase">To Accept</div>
                    </div>
                </a>

                <a href="{{ route('seller.orders') }}" class="p-3.5 bg-purple-50/60 rounded-xl border border-purple-100 hover:border-purple-300 transition-all flex items-center gap-3">
                    <div class="w-8 h-8 rounded-lg bg-purple-100 text-purple-700 flex items-center justify-center font-black text-sm shrink-0">{{ $salesSummary['customOrders'] ?? 0 }}</div>
                    <div class="min-w-0">
                        <div class="text-xs font-bold text-black truncate">Custom Orders</div>
                        <div class="text-[9px] text-purple-700 font-bold uppercase">Bespoke Fit</div>
                    </div>
                </a>

                <a href="{{ route('seller.orders') }}" class="p-3.5 bg-blue-50/60 rounded-xl border border-blue-100 hover:border-blue-300 transition-all flex items-center gap-3">
                    <div class="w-8 h-8 rounded-lg bg-blue-100 text-blue-700 flex items-center justify-center font-black text-sm shrink-0">{{ $salesSummary['readyToShip'] ?? 0 }}</div>
                    <div class="min-w-0">
                        <div class="text-xs font-bold text-black truncate">To Ship</div>
                        <div class="text-[9px] text-blue-700 font-bold uppercase">In Packing</div>
                    </div>
                </a>

                <a href="{{ route('seller.orders') }}" class="p-3.5 bg-green-50/60 rounded-xl border border-green-100 hover:border-green-300 transition-all flex items-center gap-3">
                    <div class="w-8 h-8 rounded-lg bg-green-100 text-green-700 flex items-center justify-center font-black text-sm shrink-0">{{ $salesSummary['completedOrders'] ?? 0 }}</div>
                    <div class="min-w-0">
                        <div class="text-xs font-bold text-black truncate">Completed</div>
                        <div class="text-[9px] text-green-700 font-bold uppercase">Delivered</div>
                    </div>
                </a>
            </div>
        </div>
    </div>

    {{-- SECTION 3: STORE PERFORMANCE & REVENUE CHART --}}
    <div class="space-y-4">
        <div class="flex items-center justify-between">
            <h2 class="text-xs font-black uppercase tracking-widest text-gray-400">Store Performance</h2>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-12 gap-4 sm:gap-6">
            <!-- Revenue Trend Chart -->
            <div class="lg:col-span-8 bg-white p-4 sm:p-6 rounded-2xl sm:rounded-3xl border border-gray-100 shadow-sm space-y-4">
                <div class="flex items-center justify-between">
                    <div>
                        <h3 class="text-xs sm:text-sm font-bold text-black uppercase">Revenue Trend</h3>
                        <p class="text-[10px] text-[#C0422A] font-bold uppercase tracking-widest">Period: {{ $filters['label'] ?? 'All Time' }}</p>
                    </div>
                </div>
                <div class="overflow-x-auto -mx-2 px-2">
                    <div class="flex items-end justify-between gap-2.5 h-40 min-w-70">
                        @foreach($revenueChart as $day)
                            <div class="flex-1 flex flex-col items-center gap-2">
                                <div class="w-full flex items-end justify-center h-28">
                                    @php $barHeightPct = $maxChartRevenue > 0 ? max(8, ($day['revenue'] / $maxChartRevenue) * 100) : 8; @endphp
                                    <div class="w-full max-w-10 bg-[#C0422A]/10 rounded-t-lg relative group js-bar-height" data-bar-height="{{ $barHeightPct }}">
                                        <div class="absolute inset-x-0 bottom-0 bg-[#C0422A] rounded-t-lg transition-all" style="height:100%"></div>
                                    </div>
                                </div>
                                <div class="text-center">
                                    <div class="text-[9px] font-black text-gray-400 uppercase">{{ $day['label'] }}</div>
                                    <div class="text-[9px] font-bold text-black">₱{{ number_format($day['revenue']) }}</div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>

            <!-- Store Key Metrics Matrix -->
            <div class="lg:col-span-4 bg-white p-4 sm:p-6 rounded-2xl sm:rounded-3xl border border-gray-100 shadow-sm space-y-3">
                <h3 class="text-xs sm:text-sm font-bold text-black uppercase mb-1">Key Performance Metrics</h3>
                
                <div class="grid grid-cols-2 gap-3">
                    <div class="p-3 bg-gray-50/60 rounded-xl border border-gray-100">
                        <div class="text-[9px] font-bold text-gray-400 uppercase tracking-widest">Store Rating</div>
                        <div class="text-base font-black text-amber-500 mt-0.5 flex items-center gap-1">
                            <span>{{ $storePerformance['rating'] ?? 5.0 }}</span>
                            <span class="text-xs">★</span>
                        </div>
                    </div>

                    <div class="p-3 bg-gray-50/60 rounded-xl border border-gray-100">
                        <div class="text-[9px] font-bold text-gray-400 uppercase tracking-widest">Total Followers</div>
                        <div class="text-base font-black text-black mt-0.5">{{ $storePerformance['followers'] ?? 0 }}</div>
                    </div>

                    <div class="p-3 bg-gray-50/60 rounded-xl border border-gray-100">
                        <div class="text-[9px] font-bold text-gray-400 uppercase tracking-widest">Product Views</div>
                        <div class="text-base font-black text-black mt-0.5">{{ number_format($storePerformance['productViews'] ?? 0) }}</div>
                    </div>

                    <div class="p-3 bg-gray-50/60 rounded-xl border border-gray-100">
                        <div class="text-[9px] font-bold text-gray-400 uppercase tracking-widest">Conversion</div>
                        <div class="text-base font-black text-[#C0422A] mt-0.5">{{ $storePerformance['conversionRate'] ?? '0%' }}</div>
                    </div>

                    <div class="p-3 bg-gray-50/60 rounded-xl border border-gray-100">
                        <div class="text-[9px] font-bold text-gray-400 uppercase tracking-widest">Repeat Buyers</div>
                        <div class="text-base font-black text-black mt-0.5">{{ $storePerformance['repeatCustomers'] ?? 0 }}</div>
                    </div>

                    <div class="p-3 bg-gray-50/60 rounded-xl border border-gray-100">
                        <div class="text-[9px] font-bold text-gray-400 uppercase tracking-widest">Total Customers</div>
                        <div class="text-base font-black text-black mt-0.5">{{ $storePerformance['totalCustomers'] ?? 0 }}</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- SECTION 4: BEST SELLING PRODUCTS & RECENT ACTIVITY --}}
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-4 sm:gap-6">
        <!-- Best Selling Products -->
        <div class="lg:col-span-7 bg-white p-4 sm:p-6 rounded-2xl sm:rounded-3xl border border-gray-100 shadow-sm space-y-4">
            <div class="flex items-center justify-between">
                <div>
                    <h3 class="text-xs sm:text-sm font-bold text-black uppercase">Best Selling Products</h3>
                    <p class="text-[9px] text-gray-400 uppercase tracking-widest">Top performing heritage pieces</p>
                </div>
                <a href="{{ route('seller.products.index') }}" class="text-[10px] font-bold text-[#C0422A] hover:underline">Catalogue &rarr;</a>
            </div>

            <div class="space-y-2.5">
                @forelse($topProducts as $idx => $prod)
                    <div class="flex items-center justify-between p-3 bg-gray-50/50 rounded-2xl border border-gray-100 hover:border-gray-200 transition-all gap-3">
                        <div class="flex items-center gap-3 min-w-0">
                            <span class="w-5 h-5 rounded-full bg-[#C0422A]/10 text-[#C0422A] text-[10px] font-black flex items-center justify-center shrink-0">#{{ $idx + 1 }}</span>
                            <div class="w-10 h-10 rounded-xl overflow-hidden bg-white border border-gray-100 shrink-0 shadow-xs">
                                <img src="{{ $prod->image }}" class="w-full h-full object-cover">
                            </div>
                            <div class="min-w-0">
                                <div class="text-xs font-bold text-black truncate uppercase">{{ $prod->name }}</div>
                                <div class="text-[9px] text-gray-400 font-bold">Stock: {{ $prod->stock }} pcs</div>
                            </div>
                        </div>
                        <div class="text-right shrink-0">
                            <div class="text-xs font-black text-[#C0422A]">₱{{ number_format($prod->revenue) }}</div>
                            <div class="text-[9px] font-bold text-gray-500 uppercase">{{ $prod->units_sold }} Sold</div>
                        </div>
                    </div>
                @empty
                    <div class="p-6 text-center text-gray-400 text-xs italic">
                        No sales recorded for top products yet.
                    </div>
                @endforelse
            </div>
        </div>

        <!-- Recent Orders Activity -->
        <div class="lg:col-span-5 bg-white p-4 sm:p-6 rounded-2xl sm:rounded-3xl border border-gray-100 shadow-sm space-y-4">
            <div class="flex items-center justify-between">
                <div>
                    <h3 class="text-xs sm:text-sm font-bold text-black uppercase">Recent Orders</h3>
                    <p class="text-[9px] text-gray-400 uppercase tracking-widest">Latest incoming transactions</p>
                </div>
                <a href="{{ route('seller.orders') }}" class="text-[10px] font-bold text-[#C0422A] hover:underline">All Orders &rarr;</a>
            </div>

            <div class="space-y-2.5">
                @forelse($recentActivity as $order)
                    <a href="{{ route('seller.orders') }}" class="flex items-center justify-between p-3 bg-gray-50/50 rounded-2xl border border-gray-100 hover:border-[#C0422A]/30 transition-all gap-3 group">
                        <div class="min-w-0">
                            <div class="text-xs font-bold text-black group-hover:text-[#C0422A] transition-colors truncate">#LB-{{ strtoupper(substr($order['id'], -8)) }}</div>
                            <div class="text-[9px] text-gray-400 font-medium">{{ \Carbon\Carbon::parse($order['date'])->diffForHumans() }}</div>
                        </div>
                        <div class="text-right shrink-0">
                            <div class="text-xs font-black text-black">₱{{ number_format($order['amount'], 2) }}</div>
                            <span class="inline-block px-2 py-0.5 rounded-full text-[8px] font-black uppercase tracking-wider {{ strtolower($order['status']) === 'completed' || strtolower($order['status']) === 'delivered' ? 'bg-green-50 text-green-700' : (strtolower($order['status']) === 'pending' ? 'bg-amber-50 text-amber-700' : 'bg-blue-50 text-blue-700') }}">
                                {{ $order['status'] }}
                            </span>
                        </div>
                    </a>
                @empty
                    <div class="p-6 text-center text-gray-400 text-xs italic">
                        No recent orders to show.
                    </div>
                @endforelse
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    document.querySelectorAll('.js-bar-height').forEach(function(el) {
        const pct = el.getAttribute('data-bar-height');
        const bar = el.querySelector('div');
        if (bar && pct) {
            bar.style.height = pct + '%';
        }
    });
});
</script>
@endsection
