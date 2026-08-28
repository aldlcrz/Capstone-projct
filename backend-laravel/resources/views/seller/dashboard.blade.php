@extends('layouts.seller')

@section('content')
<div class="space-y-6 sm:space-y-8">
    {{-- Header & Date Filter Toolbar --}}
    <div class="flex flex-col md:flex-row md:items-end justify-between gap-4 pb-2 border-b" style="border-color: #E8DECB;">
        <div>
            <div class="flex items-center gap-2 mb-1">
                <span class="text-[9px] font-extrabold uppercase tracking-[0.25em]" style="color: #C49520;">✦ Studio Overview</span>
                <span class="text-xs" style="color: #E8DECB;">•</span>
                <span class="text-[10px] font-semibold tracking-wider uppercase" style="color: #766C60;">
                    {{ auth()->user()->isPremiumActive() ? 'Premium Artisan' : 'Verified Artisan' }}
                </span>
            </div>
            <h1 class="font-serif text-2xl sm:text-3xl lg:text-4xl font-bold tracking-tight" style="color: #1E1915;">
                Good day, <span class="italic font-normal" style="color: #766C60;">{{ auth()->user()->name }}</span>
            </h1>
            <p class="text-xs sm:text-sm font-medium mt-1" style="color: #766C60;">
                Manage your handcrafted creations, orders, and studio performance.
            </p>
        </div>

        {{-- Filter Toolbar --}}
        <form method="GET" action="{{ route('seller.dashboard') }}" x-data="{ selectedPreset: '{{ $filters['preset'] ?? 'all_time' }}' }" class="flex items-center gap-2.5 flex-wrap">
            <div class="relative flex items-center rounded-xl px-3.5 py-2 transition-all shadow-xs" style="background: #FDF8EE; border: 1px solid #E8DECB;">
                <svg class="w-4 h-4 shrink-0 mr-2.5" style="color: #C49520;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                </svg>
                <select name="date_preset" x-model="selectedPreset" @change="$el.form.submit()" class="bg-transparent text-xs font-bold outline-none cursor-pointer appearance-none pr-6 font-sans" style="color: #1E1915;">
                    <option value="all_time" {{ in_array(($filters['preset'] ?? ''), ['all_time', '']) ? 'selected' : '' }}>All Time</option>
                    <option value="today" {{ ($filters['preset'] ?? '') == 'today' ? 'selected' : '' }}>Today</option>
                    <option value="1_week" {{ in_array(($filters['preset'] ?? ''), ['1_week', 'last_7_days']) ? 'selected' : '' }}>Last 7 Days</option>
                    <option value="1_month" {{ in_array(($filters['preset'] ?? ''), ['1_month', 'last_30_days', 'this_month']) ? 'selected' : '' }}>Last 30 Days</option>
                    <option value="1_year" {{ in_array(($filters['preset'] ?? ''), ['1_year', 'last_365_days']) ? 'selected' : '' }}>Last 1 Year</option>
                </select>
                <svg class="w-3.5 h-3.5 absolute right-3 pointer-events-none" style="color: #766C60;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                </svg>
            </div>

            @if(($filters['preset'] ?? 'all_time') !== 'all_time')
                <a href="{{ route('seller.dashboard') }}" class="text-[10px] font-bold uppercase tracking-widest px-2 py-1.5 rounded-lg transition-colors" style="color: #766C60;" onmouseover="this.style.color='#C49520';" onmouseout="this.style.color='#766C60';">
                    Reset ✕
                </a>
            @endif

            {{-- Export Button (Espresso theme) --}}
            <a href="{{ route('seller.export', request()->all()) }}" class="flex items-center gap-2 px-4 py-2 text-white rounded-xl text-xs font-bold shrink-0 transition-all shadow-xs" style="background: #1E1915;" onmouseover="this.style.background='#C49520';" onmouseout="this.style.background='#1E1915';">
                <svg class="w-3.5 h-3.5" style="color: #C49520;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path>
                </svg>
                <span>Export Report</span>
            </a>
        </form>
    </div>

    {{-- SECTION 1: QUICK ACTION ALERTS (Unified Artisan Style) --}}
    <div class="space-y-3">
        <div class="flex items-center justify-between">
            <h2 class="text-[10px] font-extrabold uppercase tracking-[0.2em] flex items-center gap-2" style="color: #766C60;">
                <span class="w-1.5 h-1.5 rounded-full" style="background: #C49520;"></span>
                Studio Action Items & Inquiries
            </h2>
        </div>

        <div class="grid grid-cols-2 lg:grid-cols-4 gap-3 sm:gap-4">
            <!-- New Orders Alert -->
            <a href="{{ route('seller.orders') }}" class="p-4 sm:p-5 rounded-2xl transition-all block group relative overflow-hidden" style="background: #FFFCF7; border: 1px solid #E8DECB; box-shadow: 0 2px 8px rgba(30,25,21,0.03);" onmouseover="this.style.borderColor='#C49520'; this.style.transform='translateY(-2px)';" onmouseout="this.style.borderColor='#E8DECB'; this.style.transform='none';">
                <div class="flex items-center justify-between mb-3">
                    <div class="w-8 h-8 rounded-xl flex items-center justify-center shrink-0" style="background: #FDF8EE; color: #C49520; border: 1px solid #E8DECB;">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/></svg>
                    </div>
                    <span class="text-[8px] font-black uppercase tracking-wider px-2 py-0.5 rounded-full font-sans" style="{{ ($quickAlerts['newOrders'] ?? 0) > 0 ? 'background:#FDF8EE; color:#A16D19; border:1px solid #E8DECB;' : 'background:#F7F4EC; color:#A09585;' }}">
                        {{ ($quickAlerts['newOrders'] ?? 0) > 0 ? 'Action Req.' : 'Clear' }}
                    </span>
                </div>
                <div class="text-xl sm:text-2xl font-black font-sans" style="color: #1E1915;">{{ $quickAlerts['newOrders'] ?? 0 }}</div>
                <div class="text-[9px] font-bold uppercase tracking-wider mt-1" style="color: #766C60;">New Orders (24h)</div>
            </a>

            <!-- Low Stock Alert -->
            <a href="{{ route('seller.products.index') }}" class="p-4 sm:p-5 rounded-2xl transition-all block group relative overflow-hidden" style="background: #FFFCF7; border: 1px solid #E8DECB; box-shadow: 0 2px 8px rgba(30,25,21,0.03);" onmouseover="this.style.borderColor='#C49520'; this.style.transform='translateY(-2px)';" onmouseout="this.style.borderColor='#E8DECB'; this.style.transform='none';">
                <div class="flex items-center justify-between mb-3">
                    <div class="w-8 h-8 rounded-xl flex items-center justify-center shrink-0" style="background: #FDF8EE; color: #C49520; border: 1px solid #E8DECB;">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                    </div>
                    <span class="text-[8px] font-black uppercase tracking-wider px-2 py-0.5 rounded-full font-sans" style="{{ ($quickAlerts['lowStock'] ?? 0) > 0 ? 'background:#FDF8EE; color:#A16D19; border:1px solid #E8DECB;' : 'background:#F7F4EC; color:#A09585;' }}">
                        {{ ($quickAlerts['lowStock'] ?? 0) > 0 ? 'Restock' : 'Healthy' }}
                    </span>
                </div>
                <div class="text-xl sm:text-2xl font-black font-sans" style="color: #1E1915;">{{ $quickAlerts['lowStock'] ?? 0 }}</div>
                <div class="text-[9px] font-bold uppercase tracking-wider mt-1" style="color: #766C60;">Low Stock Items</div>
            </a>

            <!-- New Reviews Alert -->
            <a href="{{ route('seller.profile') }}" class="p-4 sm:p-5 rounded-2xl transition-all block group relative overflow-hidden" style="background: #FFFCF7; border: 1px solid #E8DECB; box-shadow: 0 2px 8px rgba(30,25,21,0.03);" onmouseover="this.style.borderColor='#C49520'; this.style.transform='translateY(-2px)';" onmouseout="this.style.borderColor='#E8DECB'; this.style.transform='none';">
                <div class="flex items-center justify-between mb-3">
                    <div class="w-8 h-8 rounded-xl flex items-center justify-center shrink-0" style="background: #FDF8EE; color: #C49520; border: 1px solid #E8DECB;">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z"/></svg>
                    </div>
                    <span class="text-[8px] font-black uppercase tracking-wider px-2 py-0.5 rounded-full font-sans" style="background: #FDF8EE; color: #A16D19; border: 1px solid #E8DECB;">
                        7 Days
                    </span>
                </div>
                <div class="text-xl sm:text-2xl font-black font-sans" style="color: #1E1915;">{{ $quickAlerts['newReviews'] ?? 0 }}</div>
                <div class="text-[9px] font-bold uppercase tracking-wider mt-1" style="color: #766C60;">Artisan Reviews</div>
            </a>

            <!-- Messages Alert -->
            <a href="{{ route('seller.messages') }}" class="p-4 sm:p-5 rounded-2xl transition-all block group relative overflow-hidden" style="background: #FFFCF7; border: 1px solid #E8DECB; box-shadow: 0 2px 8px rgba(30,25,21,0.03);" onmouseover="this.style.borderColor='#C49520'; this.style.transform='translateY(-2px)';" onmouseout="this.style.borderColor='#E8DECB'; this.style.transform='none';">
                <div class="flex items-center justify-between mb-3">
                    <div class="w-8 h-8 rounded-xl flex items-center justify-center shrink-0" style="background: #FDF8EE; color: #C49520; border: 1px solid #E8DECB;">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/></svg>
                    </div>
                    <span class="text-[8px] font-black uppercase tracking-wider px-2 py-0.5 rounded-full font-sans" style="{{ ($quickAlerts['messages'] ?? 0) > 0 ? 'background:#FDF8EE; color:#A16D19; border:1px solid #E8DECB;' : 'background:#F7F4EC; color:#A09585;' }}">
                        Inbox
                    </span>
                </div>
                <div class="text-xl sm:text-2xl font-black font-sans" style="color: #1E1915;">{{ $quickAlerts['messages'] ?? 0 }}</div>
                <div class="text-[9px] font-bold uppercase tracking-wider mt-1" style="color: #766C60;">Buyer Inquiries</div>
            </a>
        </div>
    </div>

    {{-- SECTION 2: SALES SUMMARY --}}
    <div class="space-y-4">
        <div class="flex items-center justify-between">
            <h2 class="text-[10px] font-extrabold uppercase tracking-[0.2em]" style="color: #766C60;">Financial Summary</h2>
        </div>

        {{-- Sales Timeline Cards --}}
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-3 sm:gap-4">
            <!-- Today's Sales -->
            <div class="p-4 sm:p-6 rounded-2xl relative overflow-hidden" style="background: #FFFCF7; border: 1px solid #E8DECB; box-shadow: 0 2px 8px rgba(30,25,21,0.03);">
                <div class="text-[9px] sm:text-[10px] font-bold uppercase tracking-widest mb-1.5" style="color: #766C60;">Today's Revenue</div>
                <div class="text-base sm:text-2xl font-black font-sans tracking-tight" style="color: #C49520;">₱{{ number_format($salesSummary['todaySales'] ?? 0, 2) }}</div>
                <div class="text-[9px] font-medium mt-1.5 uppercase tracking-wider" style="color: #A09585;">Active Day Cycle</div>
            </div>

            <!-- Weekly Sales -->
            <div class="p-4 sm:p-6 rounded-2xl relative overflow-hidden" style="background: #FFFCF7; border: 1px solid #E8DECB; box-shadow: 0 2px 8px rgba(30,25,21,0.03);">
                <div class="text-[9px] sm:text-[10px] font-bold uppercase tracking-widest mb-1.5" style="color: #766C60;">Weekly Revenue</div>
                <div class="text-base sm:text-2xl font-black font-sans tracking-tight" style="color: #1E1915;">₱{{ number_format($salesSummary['weeklySales'] ?? 0, 2) }}</div>
                <div class="text-[9px] font-medium mt-1.5 uppercase tracking-wider" style="color: #A09585;">Last 7 Days</div>
            </div>

            <!-- Monthly Sales -->
            <div class="p-4 sm:p-6 rounded-2xl relative overflow-hidden" style="background: #FFFCF7; border: 1px solid #E8DECB; box-shadow: 0 2px 8px rgba(30,25,21,0.03);">
                <div class="text-[9px] sm:text-[10px] font-bold uppercase tracking-widest mb-1.5" style="color: #766C60;">Monthly Revenue</div>
                <div class="text-base sm:text-2xl font-black font-sans tracking-tight" style="color: #1E1915;">₱{{ number_format($salesSummary['monthlySales'] ?? 0, 2) }}</div>
                <div class="text-[9px] font-medium mt-1.5 uppercase tracking-wider" style="color: #A09585;">Last 30 Days</div>
            </div>

            <!-- Total Revenue -->
            <div class="p-4 sm:p-6 rounded-2xl relative overflow-hidden" style="background: #FFFCF7; border: 1px solid #E8DECB; box-shadow: 0 2px 8px rgba(30,25,21,0.03);">
                <div class="text-[9px] sm:text-[10px] font-bold uppercase tracking-widest mb-1.5" style="color: #766C60;">Cumulative Revenue</div>
                <div class="text-base sm:text-2xl font-black font-sans tracking-tight" style="color: #1E1915;">₱{{ number_format($salesSummary['totalRevenue'] ?? 0, 2) }}</div>
                <div class="text-[9px] font-medium mt-1.5 uppercase tracking-wider font-sans" style="color: #A09585;">{{ $salesSummary['totalOrders'] ?? 0 }} Total Orders Fulfilled</div>
            </div>
        </div>

        {{-- Order Pipeline Status Grid --}}
        <div class="p-4 sm:p-6 rounded-2xl sm:rounded-3xl space-y-3" style="background: #FFFCF7; border: 1px solid #E8DECB; box-shadow: 0 2px 8px rgba(30,25,21,0.03);">
            <div class="text-xs font-bold uppercase tracking-widest flex items-center justify-between" style="color: #1E1915;">
                <span class="font-serif text-sm">Order Fulfillment Pipeline</span>
                <a href="{{ route('seller.orders') }}" class="text-[10px] font-extrabold uppercase tracking-wider hover:underline" style="color: #C49520;">View All Orders &rarr;</a>
            </div>

            <div class="grid grid-cols-2 sm:grid-cols-4 gap-2.5 sm:gap-4">
                <a href="{{ route('seller.orders') }}" class="p-3.5 rounded-xl transition-all flex items-center gap-3" style="background: #FDF8EE; border: 1px solid #E8DECB;" onmouseover="this.style.borderColor='#C49520';" onmouseout="this.style.borderColor='#E8DECB';">
                    <div class="w-8 h-8 rounded-lg flex items-center justify-center font-bold text-sm shrink-0 font-sans" style="background: #1E1915; color: #C49520;">{{ $salesSummary['pendingOrders'] ?? 0 }}</div>
                    <div class="min-w-0">
                        <div class="text-xs font-bold truncate" style="color: #1E1915;">Pending</div>
                        <div class="text-[9px] font-bold uppercase tracking-wider" style="color: #766C60;">Awaiting Action</div>
                    </div>
                </a>

                <a href="{{ route('seller.orders') }}" class="p-3.5 rounded-xl transition-all flex items-center gap-3" style="background: #FDF8EE; border: 1px solid #E8DECB;" onmouseover="this.style.borderColor='#C49520';" onmouseout="this.style.borderColor='#E8DECB';">
                    <div class="w-8 h-8 rounded-lg flex items-center justify-center font-bold text-sm shrink-0 font-sans" style="background: #1E1915; color: #C49520;">{{ $salesSummary['customOrders'] ?? 0 }}</div>
                    <div class="min-w-0">
                        <div class="text-xs font-bold truncate" style="color: #1E1915;">Custom Fit</div>
                        <div class="text-[9px] font-bold uppercase tracking-wider" style="color: #766C60;">Bespoke Orders</div>
                    </div>
                </a>

                <a href="{{ route('seller.orders') }}" class="p-3.5 rounded-xl transition-all flex items-center gap-3" style="background: #FDF8EE; border: 1px solid #E8DECB;" onmouseover="this.style.borderColor='#C49520';" onmouseout="this.style.borderColor='#E8DECB';">
                    <div class="w-8 h-8 rounded-lg flex items-center justify-center font-bold text-sm shrink-0 font-sans" style="background: #1E1915; color: #C49520;">{{ $salesSummary['readyToShip'] ?? 0 }}</div>
                    <div class="min-w-0">
                        <div class="text-xs font-bold truncate" style="color: #1E1915;">To Ship</div>
                        <div class="text-[9px] font-bold uppercase tracking-wider" style="color: #766C60;">In Packaging</div>
                    </div>
                </a>

                <a href="{{ route('seller.orders') }}" class="p-3.5 rounded-xl transition-all flex items-center gap-3" style="background: #FDF8EE; border: 1px solid #E8DECB;" onmouseover="this.style.borderColor='#C49520';" onmouseout="this.style.borderColor='#E8DECB';">
                    <div class="w-8 h-8 rounded-lg flex items-center justify-center font-bold text-sm shrink-0 font-sans" style="background: #1E1915; color: #C49520;">{{ $salesSummary['completedOrders'] ?? 0 }}</div>
                    <div class="min-w-0">
                        <div class="text-xs font-bold truncate" style="color: #1E1915;">Delivered</div>
                        <div class="text-[9px] font-bold uppercase tracking-wider" style="color: #766C60;">Completed</div>
                    </div>
                </a>
            </div>
        </div>
    </div>

    {{-- SECTION 3: STORE PERFORMANCE & REVENUE CHART --}}
    <div class="space-y-4">
        <div class="flex items-center justify-between">
            <h2 class="text-[10px] font-extrabold uppercase tracking-[0.2em]" style="color: #766C60;">Artisan Performance Metrics</h2>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-12 gap-4 sm:gap-6">
            <!-- Revenue Trend Chart (Amber bars) -->
            <div class="lg:col-span-8 p-4 sm:p-6 rounded-2xl sm:rounded-3xl space-y-4" style="background: #FFFCF7; border: 1px solid #E8DECB; box-shadow: 0 2px 8px rgba(30,25,21,0.03);">
                <div class="flex items-center justify-between">
                    <div>
                        <h3 class="font-serif text-sm sm:text-base font-bold uppercase" style="color: #1E1915;">Revenue Trajectory</h3>
                        <p class="text-[10px] font-bold uppercase tracking-widest mt-0.5" style="color: #C49520;">Period: {{ $filters['label'] ?? 'All Time' }}</p>
                    </div>
                </div>
                <div class="overflow-x-auto -mx-2 px-2 no-scrollbar">
                    <div class="flex items-end justify-between gap-2.5 h-40 min-w-70">
                        @foreach($revenueChart as $day)
                            <div class="flex-1 flex flex-col items-center gap-2">
                                <div class="w-full flex items-end justify-center h-28">
                                    @php $barHeightPct = $maxChartRevenue > 0 ? max(8, ($day['revenue'] / $maxChartRevenue) * 100) : 8; @endphp
                                    <div class="h-full w-full max-w-10 rounded-t-lg relative group flex items-end justify-center" style="background: #FDF8EE; border: 1px solid rgba(232,222,203,0.5);">
                                        <div class="w-full rounded-t-lg transition-all duration-300" style="background: #B5870F; height: {{ $barHeightPct }}%;" onmouseover="this.style.background='#A16D19';" onmouseout="this.style.background='#B5870F';"></div>
                                    </div>
                                </div>
                                <div class="text-center">
                                    <div class="text-[9px] font-bold uppercase" style="color: #766C60;">{{ $day['label'] }}</div>
                                    <div class="text-[9px] font-bold font-sans" style="color: #1E1915;">₱{{ number_format($day['revenue']) }}</div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>

            <!-- Store Key Metrics Matrix -->
            <div class="lg:col-span-4 p-4 sm:p-6 rounded-2xl sm:rounded-3xl space-y-3" style="background: #FFFCF7; border: 1px solid #E8DECB; box-shadow: 0 2px 8px rgba(30,25,21,0.03);">
                <h3 class="font-serif text-sm sm:text-base font-bold uppercase mb-1" style="color: #1E1915;">Studio Benchmark</h3>
                
                <div class="grid grid-cols-2 gap-3">
                    <div class="p-3 rounded-xl" style="background: #FDF8EE; border: 1px solid #E8DECB;">
                        <div class="text-[9px] font-bold uppercase tracking-widest" style="color: #766C60;">Studio Rating</div>
                        <div class="text-base font-black mt-0.5 flex items-center gap-1 font-sans" style="color: #C49520;">
                            <span>{{ $storePerformance['rating'] ?? 5.0 }}</span>
                            <span class="text-xs">★</span>
                        </div>
                    </div>

                    <div class="p-3 rounded-xl" style="background: #FDF8EE; border: 1px solid #E8DECB;">
                        <div class="text-[9px] font-bold uppercase tracking-widest" style="color: #766C60;">Followers</div>
                        <div class="text-base font-black mt-0.5 font-sans" style="color: #1E1915;">{{ $storePerformance['followers'] ?? 0 }}</div>
                    </div>

                    <div class="p-3 rounded-xl" style="background: #FDF8EE; border: 1px solid #E8DECB;">
                        <div class="text-[9px] font-bold uppercase tracking-widest" style="color: #766C60;">Catalogue Views</div>
                        <div class="text-base font-black mt-0.5 font-sans" style="color: #1E1915;">{{ number_format($storePerformance['productViews'] ?? 0) }}</div>
                    </div>

                    <div class="p-3 rounded-xl" style="background: #FDF8EE; border: 1px solid #E8DECB;">
                        <div class="text-[9px] font-bold uppercase tracking-widest" style="color: #766C60;">Conversion</div>
                        <div class="text-base font-black mt-0.5 font-sans" style="color: #C49520;">{{ $storePerformance['conversionRate'] ?? '0%' }}</div>
                    </div>

                    <div class="p-3 rounded-xl" style="background: #FDF8EE; border: 1px solid #E8DECB;">
                        <div class="text-[9px] font-bold uppercase tracking-widest" style="color: #766C60;">Repeat Patrons</div>
                        <div class="text-base font-black mt-0.5 font-sans" style="color: #1E1915;">{{ $storePerformance['repeatCustomers'] ?? 0 }}</div>
                    </div>

                    <div class="p-3 rounded-xl" style="background: #FDF8EE; border: 1px solid #E8DECB;">
                        <div class="text-[9px] font-bold uppercase tracking-widest" style="color: #766C60;">Total Clients</div>
                        <div class="text-base font-black mt-0.5 font-sans" style="color: #1E1915;">{{ $storePerformance['totalCustomers'] ?? 0 }}</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- SECTION 4: BEST SELLING CREATIONS & RECENT TRANSACTIONS --}}
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-4 sm:gap-6">
        <!-- Best Selling Products -->
        <div class="lg:col-span-7 p-4 sm:p-6 rounded-2xl sm:rounded-3xl space-y-4" style="background: #FFFCF7; border: 1px solid #E8DECB; box-shadow: 0 2px 8px rgba(30,25,21,0.03);">
            <div class="flex items-center justify-between">
                <div>
                    <h3 class="font-serif text-sm sm:text-base font-bold uppercase" style="color: #1E1915;">Heritage Signatures</h3>
                    <p class="text-[9px] uppercase tracking-widest mt-0.5" style="color: #766C60;">Top performing handcrafted pieces</p>
                </div>
                <a href="{{ route('seller.products.index') }}" class="text-[10px] font-extrabold uppercase tracking-wider hover:underline" style="color: #C49520;">Catalogue &rarr;</a>
            </div>

            <div class="space-y-2.5">
                @forelse($topProducts as $idx => $prod)
                    <div class="flex items-center justify-between p-3 rounded-2xl transition-all gap-3" style="background: #FDF8EE; border: 1px solid #E8DECB;">
                        <div class="flex items-center gap-3 min-w-0">
                            <span class="w-5 h-5 rounded-full text-[9px] font-black flex items-center justify-center shrink-0 font-sans" style="background: #1E1915; color: #C49520;">#{{ $idx + 1 }}</span>
                            <div class="w-10 h-10 rounded-xl overflow-hidden shrink-0 shadow-xs" style="background: #FFF; border: 1px solid #E8DECB;">
                                <img src="{{ $prod->image }}" class="w-full h-full object-cover">
                            </div>
                            <div class="min-w-0">
                                <div class="text-xs font-bold truncate uppercase tracking-tight" style="color: #1E1915;">{{ $prod->name }}</div>
                                <div class="text-[9px] font-medium font-sans" style="color: #766C60;">Available Stock: {{ $prod->stock }} pcs</div>
                            </div>
                        </div>
                        <div class="text-right shrink-0">
                            <div class="text-xs font-black font-sans" style="color: #C49520;">₱{{ number_format($prod->revenue) }}</div>
                            <div class="text-[9px] font-bold uppercase font-sans" style="color: #766C60;">{{ $prod->units_sold }} Sold</div>
                        </div>
                    </div>
                @empty
                    <div class="p-6 text-center text-xs italic" style="color: #A09585;">
                        No sales recorded for signature pieces yet.
                    </div>
                @endforelse
            </div>
        </div>

        <!-- Recent Orders Activity -->
        <div class="lg:col-span-5 p-4 sm:p-6 rounded-2xl sm:rounded-3xl space-y-4" style="background: #FFFCF7; border: 1px solid #E8DECB; box-shadow: 0 2px 8px rgba(30,25,21,0.03);">
            <div class="flex items-center justify-between">
                <div>
                    <h3 class="font-serif text-sm sm:text-base font-bold uppercase" style="color: #1E1915;">Recent Acquisitions</h3>
                    <p class="text-[9px] uppercase tracking-widest mt-0.5" style="color: #766C60;">Latest incoming transactions</p>
                </div>
                <a href="{{ route('seller.orders') }}" class="text-[10px] font-extrabold uppercase tracking-wider hover:underline" style="color: #C49520;">All Orders &rarr;</a>
            </div>

            <div class="space-y-2.5">
                @forelse($recentActivity as $order)
                    <a href="{{ route('seller.orders') }}" class="flex items-center justify-between p-3 rounded-2xl transition-all gap-3 group" style="background: #FDF8EE; border: 1px solid #E8DECB;" onmouseover="this.style.borderColor='#C49520';" onmouseout="this.style.borderColor='#E8DECB';">
                        <div class="min-w-0">
                            <div class="text-xs font-bold transition-colors truncate font-sans" style="color: #1E1915;">#LB-{{ strtoupper(substr($order['id'], -8)) }}</div>
                            <div class="text-[9px] font-medium" style="color: #766C60;">{{ \Carbon\Carbon::parse($order['date'])->diffForHumans() }}</div>
                        </div>
                        <div class="text-right shrink-0">
                            <div class="text-xs font-black font-sans" style="color: #1E1915;">₱{{ number_format($order['amount'], 2) }}</div>
                            <span class="inline-block px-2 py-0.5 rounded-full text-[8px] font-black uppercase tracking-wider font-sans" style="background: #FFFCF7; border: 1px solid #E8DECB; color: #766C60;">
                                {{ $order['status'] }}
                            </span>
                        </div>
                    </a>
                @empty
                    <div class="p-6 text-center text-xs italic" style="color: #A09585;">
                        No recent acquisitions to display.
                    </div>
                @endforelse
            </div>
        </div>
    </div>
</div>
@endsection
