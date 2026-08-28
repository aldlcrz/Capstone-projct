@extends('layouts.seller')

@section('content')
<div x-data="{ activeTab: 'sales' }" class="space-y-6 sm:space-y-8">
    {{-- Header & Date Filter Toolbar --}}
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <div class="mb-1">
                <span class="text-[10px] font-bold uppercase tracking-[0.2em]" style="color: #A16D19;">✦ Deep Studio Insights</span>
            </div>
            <h1 class="font-serif text-2xl sm:text-3xl font-bold uppercase" style="color: #1E1915;">
                Seller <span class="italic lowercase" style="color: #C49520;">Analytics</span>
            </h1>
        </div>

        {{-- Date Filter Form --}}
        <form method="GET" action="{{ route('seller.analytics') }}" x-data="{ selectedPreset: '{{ $filters['preset'] ?? 'all_time' }}' }" class="flex items-center gap-2">
            <div class="relative flex items-center rounded-2xl px-4 py-2 shadow-2xs transition-all" style="background: #FFFFFF; border: 1px solid #E8DECB;">
                <svg class="w-4 h-4 shrink-0 mr-2" style="color: #A16D19;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                </svg>
                <select name="date_preset" x-model="selectedPreset" @change="$el.form.submit()" class="bg-transparent text-xs font-bold outline-none cursor-pointer pr-4 font-sans" style="color: #1E1915;">
                    <option value="all_time" {{ in_array(($filters['preset'] ?? ''), ['all_time', '']) ? 'selected' : '' }}>All Time</option>
                    <option value="today" {{ ($filters['preset'] ?? '') == 'today' ? 'selected' : '' }}>Today</option>
                    <option value="1_week" {{ in_array(($filters['preset'] ?? ''), ['1_week', 'last_7_days']) ? 'selected' : '' }}>1 Week</option>
                    <option value="1_month" {{ in_array(($filters['preset'] ?? ''), ['1_month', 'last_30_days']) ? 'selected' : '' }}>1 Month</option>
                    <option value="1_year" {{ in_array(($filters['preset'] ?? ''), ['1_year', 'last_365_days']) ? 'selected' : '' }}>1 Year</option>
                </select>
            </div>
            @if(($filters['preset'] ?? 'all_time') !== 'all_time')
                <a href="{{ route('seller.analytics') }}" class="text-[10px] font-bold uppercase tracking-widest px-2" style="color: #766C60;" onmouseover="this.style.color='#DC2626'" onmouseout="this.style.color='#766C60'">Reset ✕</a>
            @endif
        </form>
    </div>

    {{-- Interactive Module Tabs --}}
    <div class="overflow-x-auto no-scrollbar -mx-4 px-4 sm:mx-0 sm:px-0">
        <div class="flex items-center gap-2 border-b pb-3 min-w-max" style="border-color: #E8DECB;">
            <button @click="activeTab = 'sales'" 
                    class="px-4 py-2.5 rounded-xl text-xs font-bold transition-all flex items-center gap-2 cursor-pointer font-sans"
                    :style="activeTab === 'sales' ? 'background: #1E1915; color: #FFFCF7; box-shadow: 0 2px 8px rgba(30,25,21,0.12);' : 'background: #FFFFFF; color: #6C6256; border: 1px solid #ECE3D2;'">
                <span>💰 Sales Analytics</span>
            </button>
            <button @click="activeTab = 'orders'" 
                    class="px-4 py-2.5 rounded-xl text-xs font-bold transition-all flex items-center gap-2 cursor-pointer font-sans"
                    :style="activeTab === 'orders' ? 'background: #1E1915; color: #FFFCF7; box-shadow: 0 2px 8px rgba(30,25,21,0.12);' : 'background: #FFFFFF; color: #6C6256; border: 1px solid #ECE3D2;'">
                <span>📦 Order Analytics</span>
            </button>
            <button @click="activeTab = 'products'" 
                    class="px-4 py-2.5 rounded-xl text-xs font-bold transition-all flex items-center gap-2 cursor-pointer font-sans"
                    :style="activeTab === 'products' ? 'background: #1E1915; color: #FFFCF7; box-shadow: 0 2px 8px rgba(30,25,21,0.12);' : 'background: #FFFFFF; color: #6C6256; border: 1px solid #ECE3D2;'">
                <span>🛍️ Product Analytics</span>
            </button>
            <button @click="activeTab = 'customers'" 
                    class="px-4 py-2.5 rounded-xl text-xs font-bold transition-all flex items-center gap-2 cursor-pointer font-sans"
                    :style="activeTab === 'customers' ? 'background: #1E1915; color: #FFFCF7; box-shadow: 0 2px 8px rgba(30,25,21,0.12);' : 'background: #FFFFFF; color: #6C6256; border: 1px solid #ECE3D2;'">
                <span>👥 Customer Analytics</span>
            </button>
            <button @click="activeTab = 'category'" 
                    class="px-4 py-2.5 rounded-xl text-xs font-bold transition-all flex items-center gap-2 cursor-pointer font-sans"
                    :style="activeTab === 'category' ? 'background: #1E1915; color: #FFFCF7; box-shadow: 0 2px 8px rgba(30,25,21,0.12);' : 'background: #FFFFFF; color: #6C6256; border: 1px solid #ECE3D2;'">
                <span>🏷️ Category Demand</span>
            </button>
            <button @click="activeTab = 'financials'" 
                    class="px-4 py-2.5 rounded-xl text-xs font-bold transition-all flex items-center gap-2 cursor-pointer font-sans"
                    :style="activeTab === 'financials' ? 'background: #1E1915; color: #FFFCF7; box-shadow: 0 2px 8px rgba(30,25,21,0.12);' : 'background: #FFFFFF; color: #6C6256; border: 1px solid #ECE3D2;'">
                <span>💵 Financials & Payout</span>
            </button>
            <button @click="activeTab = 'marketing'" 
                    class="px-4 py-2.5 rounded-xl text-xs font-bold transition-all flex items-center gap-2 cursor-pointer font-sans"
                    :style="activeTab === 'marketing' ? 'background: #1E1915; color: #FFFCF7; box-shadow: 0 2px 8px rgba(30,25,21,0.12);' : 'background: #FFFFFF; color: #6C6256; border: 1px solid #ECE3D2;'">
                <span>🏷️ Promotions</span>
            </button>
        </div>
    </div>

    {{-- TAB 1: SALES ANALYTICS --}}
    <div x-show="activeTab === 'sales'" class="space-y-6">
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-3 sm:gap-6">
            <div class="p-4 sm:p-6 rounded-2xl shadow-2xs" style="background: #FFFFFF; border: 1px solid #ECE3D2;">
                <div class="text-[9px] sm:text-[10px] font-bold uppercase tracking-widest mb-1" style="color: #8C827A;">Total Sales</div>
                <div class="text-lg sm:text-2xl font-black font-sans" style="color: #C49520;">₱{{ number_format($salesAnalytics['totalSales'], 2) }}</div>
                <div class="text-[9px] font-bold mt-1 uppercase font-sans" style="color: #8C827A;">Net Sales: ₱{{ number_format($salesAnalytics['netSales'], 2) }}</div>
            </div>

            <div class="p-4 sm:p-6 rounded-2xl shadow-2xs" style="background: #FFFFFF; border: 1px solid #ECE3D2;">
                <div class="text-[9px] sm:text-[10px] font-bold uppercase tracking-widest mb-1" style="color: #8C827A;">Average Order Value</div>
                <div class="text-lg sm:text-2xl font-black font-sans" style="color: #1E1915;">₱{{ number_format($salesAnalytics['averageOrderValue'], 2) }}</div>
                <div class="text-[9px] font-bold mt-1 uppercase font-sans" style="color: #8C827A;">Per completed order</div>
            </div>

            <div class="p-4 sm:p-6 rounded-2xl shadow-2xs" style="background: #FFFFFF; border: 1px solid #ECE3D2;">
                <div class="text-[9px] sm:text-[10px] font-bold uppercase tracking-widest mb-1" style="color: #8C827A;">Total Items Sold</div>
                <div class="text-lg sm:text-2xl font-black font-sans" style="color: #1E1915;">{{ number_format($salesAnalytics['totalItemsSold']) }} pcs</div>
                <div class="text-[9px] font-bold mt-1 uppercase" style="color: #8C827A;">Heritage barong pieces</div>
            </div>

            <div class="p-4 sm:p-6 rounded-2xl shadow-2xs" style="background: #FFFFFF; border: 1px solid #ECE3D2;">
                <div class="text-[9px] sm:text-[10px] font-bold uppercase tracking-widest mb-1" style="color: #8C827A;">Monthly Growth</div>
                <div class="text-lg sm:text-2xl font-black font-sans {{ $salesAnalytics['monthGrowthPct'] >= 0 ? 'text-emerald-600' : 'text-rose-600' }}">
                    {{ $salesAnalytics['monthGrowthPct'] >= 0 ? '+' : '' }}{{ $salesAnalytics['monthGrowthPct'] }}%
                </div>
                <div class="text-[9px] font-bold mt-1 uppercase" style="color: #8C827A;">This Month vs Last Month</div>
            </div>
        </div>

        {{-- Sales Comparisons Matrix --}}
        <div class="p-5 sm:p-6 rounded-3xl shadow-2xs space-y-4" style="background: #FFFFFF; border: 1px solid #ECE3D2;">
            <h3 class="font-serif text-xs sm:text-sm font-bold uppercase tracking-wider" style="color: #1E1915;">Sales Period Comparisons</h3>
            
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                <div class="p-4 rounded-2xl" style="background: #FAF7F2; border: 1px solid #E8DECB;">
                    <div class="text-[10px] font-bold uppercase tracking-widest mb-1" style="color: #8C827A;">This Month vs Last Month</div>
                    <div class="text-base font-bold font-sans" style="color: #1E1915;">₱{{ number_format($salesAnalytics['thisMonthSales'], 2) }}</div>
                    <div class="text-[10px] mt-0.5 font-sans" style="color: #766C60;">Last Month: ₱{{ number_format($salesAnalytics['lastMonthSales'], 2) }}</div>
                    <span class="inline-block mt-2 px-2.5 py-0.5 rounded-full text-[9px] font-black uppercase font-sans {{ $salesAnalytics['monthGrowthPct'] >= 0 ? 'bg-emerald-100 text-emerald-800' : 'bg-rose-100 text-rose-800' }}">
                        {{ $salesAnalytics['monthGrowthPct'] >= 0 ? '▲' : '▼' }} {{ abs($salesAnalytics['monthGrowthPct']) }}%
                    </span>
                </div>

                <div class="p-4 rounded-2xl" style="background: #FAF7F2; border: 1px solid #E8DECB;">
                    <div class="text-[10px] font-bold uppercase tracking-widest mb-1" style="color: #8C827A;">This Week vs Last Week</div>
                    <div class="text-base font-bold font-sans" style="color: #1E1915;">₱{{ number_format($salesAnalytics['thisWeekSales'], 2) }}</div>
                    <div class="text-[10px] mt-0.5 font-sans" style="color: #766C60;">Last Week: ₱{{ number_format($salesAnalytics['prevWeekSales'], 2) }}</div>
                    <span class="inline-block mt-2 px-2.5 py-0.5 rounded-full text-[9px] font-black uppercase font-sans {{ $salesAnalytics['weekGrowthPct'] >= 0 ? 'bg-emerald-100 text-emerald-800' : 'bg-rose-100 text-rose-800' }}">
                        {{ $salesAnalytics['weekGrowthPct'] >= 0 ? '▲' : '▼' }} {{ abs($salesAnalytics['weekGrowthPct']) }}%
                    </span>
                </div>

                <div class="p-4 rounded-2xl" style="background: #FAF7F2; border: 1px solid #E8DECB;">
                    <div class="text-[10px] font-bold uppercase tracking-widest mb-1" style="color: #8C827A;">This Year vs Last Year</div>
                    <div class="text-base font-bold font-sans" style="color: #1E1915;">₱{{ number_format($salesAnalytics['thisYearSales'], 2) }}</div>
                    <div class="text-[10px] mt-0.5 font-sans" style="color: #766C60;">Last Year: ₱{{ number_format($salesAnalytics['prevYearSales'], 2) }}</div>
                    <span class="inline-block mt-2 px-2.5 py-0.5 rounded-full text-[9px] font-black uppercase font-sans {{ $salesAnalytics['yearGrowthPct'] >= 0 ? 'bg-emerald-100 text-emerald-800' : 'bg-rose-100 text-rose-800' }}">
                        {{ $salesAnalytics['yearGrowthPct'] >= 0 ? '▲' : '▼' }} {{ abs($salesAnalytics['yearGrowthPct']) }}%
                    </span>
                </div>
            </div>
        </div>

        {{-- Sales Trend Visualizer Chart --}}
        <div class="p-5 sm:p-6 rounded-3xl shadow-2xs space-y-4" style="background: #FFFFFF; border: 1px solid #ECE3D2;">
            <div class="flex items-center justify-between">
                <h3 class="font-serif text-xs sm:text-sm font-bold uppercase tracking-wider" style="color: #1E1915;">Sales Trend Overview</h3>
                <span class="text-[10px] font-bold uppercase tracking-widest" style="color: #A16D19;">Revenue Trend (₱)</span>
            </div>
            <div class="overflow-x-auto no-scrollbar">
                <div class="flex items-end justify-between gap-3 h-44 min-w-80 pt-4">
                    @foreach($salesTrendChart['points'] as $pt)
                        <div class="flex-1 flex flex-col items-center gap-2">
                            @php 
                                $heightPct = $salesTrendChart['max'] > 0 
                                    ? ($pt['revenue'] > 0 ? max(8, round(($pt['revenue'] / $salesTrendChart['max']) * 100)) : 4) 
                                    : 4; 
                            @endphp
                            <div class="w-full max-w-12 rounded-t-xl relative overflow-hidden h-32" style="background: #FAF7F2;">
                                <div class="absolute inset-x-0 bottom-0 rounded-t-xl transition-all duration-500" 
                                     :style="'height: {{ $heightPct }}%'" style="background: linear-gradient(180deg, #C49520 0%, #A16D19 100%);"></div>
                            </div>
                            <div class="text-center">
                                <div class="text-[9px] font-bold uppercase" style="color: #8C827A;">{{ $pt['label'] }}</div>
                                <div class="text-[9px] font-bold font-sans" style="color: #1E1915;">₱{{ number_format($pt['revenue']) }}</div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>

    {{-- TAB 2: ORDER ANALYTICS --}}
    <div x-show="activeTab === 'orders'" class="space-y-6">
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-3 sm:gap-6">
            <div class="p-4 sm:p-6 rounded-2xl shadow-2xs" style="background: #FFFFFF; border: 1px solid #ECE3D2;">
                <div class="text-[9px] sm:text-[10px] font-bold uppercase tracking-widest mb-1" style="color: #8C827A;">Total Orders</div>
                <div class="text-lg sm:text-2xl font-black font-sans" style="color: #1E1915;">{{ number_format($orderAnalytics['stats']['total']) }}</div>
            </div>

            <div class="p-4 sm:p-6 rounded-2xl shadow-2xs" style="background: #FFFFFF; border: 1px solid #ECE3D2;">
                <div class="text-[9px] sm:text-[10px] font-bold uppercase tracking-widest mb-1" style="color: #8C827A;">Completion Rate</div>
                <div class="text-lg sm:text-2xl font-black font-sans text-emerald-600">{{ $orderAnalytics['completionRate'] }}%</div>
            </div>

            <div class="p-4 sm:p-6 rounded-2xl shadow-2xs" style="background: #FFFFFF; border: 1px solid #ECE3D2;">
                <div class="text-[9px] sm:text-[10px] font-bold uppercase tracking-widest mb-1" style="color: #8C827A;">To Ship / Processing</div>
                <div class="text-lg sm:text-2xl font-black font-sans" style="color: #A16D19;">{{ number_format($orderAnalytics['stats']['toShip']) }}</div>
            </div>

            <div class="p-4 sm:p-6 rounded-2xl shadow-2xs" style="background: #FFFFFF; border: 1px solid #ECE3D2;">
                <div class="text-[9px] sm:text-[10px] font-bold uppercase tracking-widest mb-1" style="color: #8C827A;">Avg Processing Time</div>
                <div class="text-lg sm:text-2xl font-black font-sans" style="color: #1E1915;">~{{ $orderAnalytics['avgProcessingTimeHours'] }} hrs</div>
            </div>
        </div>

        {{-- Order Status Breakdown --}}
        <div class="p-5 sm:p-6 rounded-3xl shadow-2xs space-y-4" style="background: #FFFFFF; border: 1px solid #ECE3D2;">
            <h3 class="font-serif text-xs sm:text-sm font-bold uppercase tracking-wider" style="color: #1E1915;">Order Status Distribution</h3>
            <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
                <div class="p-4 rounded-2xl" style="background: #FEF9EE; border: 1px solid #F6E6C2;">
                    <div class="text-[10px] font-bold uppercase tracking-widest" style="color: #A16D19;">Pending Orders</div>
                    <div class="text-xl font-bold font-sans mt-1" style="color: #5C3D0E;">{{ number_format($orderAnalytics['stats']['pending']) }}</div>
                </div>

                <div class="p-4 rounded-2xl" style="background: #F0F6FF; border: 1px solid #D0E1FD;">
                    <div class="text-[10px] font-bold uppercase tracking-widest text-blue-700">To Ship / In Transit</div>
                    <div class="text-xl font-bold font-sans text-blue-950 mt-1">{{ number_format($orderAnalytics['stats']['toShip']) }}</div>
                </div>

                <div class="p-4 rounded-2xl" style="background: #F0FDF4; border: 1px solid #DCFCE7;">
                    <div class="text-[10px] font-bold uppercase tracking-widest text-emerald-700">Delivered / Completed</div>
                    <div class="text-xl font-bold font-sans text-emerald-950 mt-1">{{ number_format($orderAnalytics['stats']['completed']) }}</div>
                </div>

                <div class="p-4 rounded-2xl" style="background: #FEF2F2; border: 1px solid #FEE2E2;">
                    <div class="text-[10px] font-bold uppercase tracking-widest text-rose-700">Cancelled</div>
                    <div class="text-xl font-bold font-sans text-rose-950 mt-1">{{ number_format($orderAnalytics['stats']['cancelled']) }}</div>
                </div>
            </div>
        </div>
    </div>

    {{-- TAB 3: PRODUCT ANALYTICS --}}
    <div x-show="activeTab === 'products'" class="space-y-6">
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <!-- Best Selling Products -->
            <div class="p-5 sm:p-6 rounded-3xl shadow-2xs space-y-4" style="background: #FFFFFF; border: 1px solid #ECE3D2;">
                <div class="flex items-center justify-between">
                    <h3 class="font-serif text-xs sm:text-sm font-bold uppercase tracking-wider" style="color: #1E1915;">🔥 Best Selling Creations</h3>
                    <span class="text-[9px] font-bold uppercase tracking-wider" style="color: #C49520;">Top Revenue</span>
                </div>
                <div class="space-y-3">
                    @forelse($productAnalytics['bestSelling'] as $prod)
                        <div class="flex items-center justify-between p-3 rounded-2xl gap-3" style="background: #FAF7F2; border: 1px solid #E8DECB;">
                            <div class="flex items-center gap-3 min-w-0">
                                <img src="{{ $prod['image'] }}" class="w-10 h-10 rounded-xl object-cover border shrink-0" style="border-color: #E8DECB;">
                                <div class="min-w-0">
                                    <div class="text-xs font-bold truncate uppercase" style="color: #1E1915;">{{ $prod['name'] }}</div>
                                    <div class="text-[9px] font-bold mt-0.5 font-sans" style="color: #8C827A;">Stock: {{ $prod['stock'] }} | Rating: {{ $prod['rating'] }} ★</div>
                                </div>
                            </div>
                            <div class="text-right shrink-0">
                                <div class="text-xs font-bold font-sans" style="color: #C49520;">₱{{ number_format($prod['revenue']) }}</div>
                                <div class="text-[9px] font-bold uppercase font-sans" style="color: #8C827A;">{{ $prod['unitsSold'] }} Sold</div>
                            </div>
                        </div>
                    @empty
                        <div class="p-6 text-center text-xs italic" style="color: #8C827A;">No products recorded yet.</div>
                    @endforelse
                </div>
            </div>

            <!-- Most Viewed Products -->
            <div class="p-5 sm:p-6 rounded-3xl shadow-2xs space-y-4" style="background: #FFFFFF; border: 1px solid #ECE3D2;">
                <div class="flex items-center justify-between">
                    <h3 class="font-serif text-xs sm:text-sm font-bold uppercase tracking-wider" style="color: #1E1915;">👁️ Most Viewed Creations</h3>
                    <span class="text-[9px] font-bold uppercase tracking-wider text-blue-600">High Traffic</span>
                </div>
                <div class="space-y-3">
                    @forelse($productAnalytics['mostViewed'] as $prod)
                        <div class="flex items-center justify-between p-3 rounded-2xl gap-3" style="background: #FAF7F2; border: 1px solid #E8DECB;">
                            <div class="flex items-center gap-3 min-w-0">
                                <img src="{{ $prod['image'] }}" class="w-10 h-10 rounded-xl object-cover border shrink-0" style="border-color: #E8DECB;">
                                <div class="min-w-0">
                                    <div class="text-xs font-bold truncate uppercase" style="color: #1E1915;">{{ $prod['name'] }}</div>
                                    <div class="text-[9px] font-bold mt-0.5 font-sans" style="color: #8C827A;">Conversion: {{ $prod['conversionRate'] }}%</div>
                                </div>
                            </div>
                            <div class="text-right shrink-0">
                                <div class="text-xs font-bold font-sans" style="color: #1E1915;">{{ number_format($prod['views']) }} Views</div>
                                <div class="text-[9px] font-bold uppercase font-sans" style="color: #8C827A;">{{ $prod['orders'] }} Orders</div>
                            </div>
                        </div>
                    @empty
                        <div class="p-6 text-center text-xs italic" style="color: #8C827A;">No views recorded yet.</div>
                    @endforelse
                </div>
            </div>
        </div>

        {{-- Granular Per-Product Performance Table --}}
        <div class="p-5 sm:p-6 rounded-3xl shadow-2xs space-y-4" style="background: #FFFFFF; border: 1px solid #ECE3D2;">
            <h3 class="font-serif text-xs sm:text-sm font-bold uppercase tracking-wider" style="color: #1E1915;">Per-Product Performance Catalogue</h3>
            <div class="overflow-x-auto no-scrollbar">
                <table class="w-full text-left border-collapse min-w-140">
                    <thead>
                        <tr class="border-b text-[9px] font-bold uppercase tracking-wider" style="border-color: #E8DECB; color: #8C827A;">
                            <th class="py-3 px-2">Product</th>
                            <th class="py-3 px-2">Views</th>
                            <th class="py-3 px-2">Add to Cart</th>
                            <th class="py-3 px-2">Wishlist</th>
                            <th class="py-3 px-2">Units Sold</th>
                            <th class="py-3 px-2">Revenue</th>
                            <th class="py-3 px-2">Conversion</th>
                            <th class="py-3 px-2">Rating</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y text-xs font-medium font-sans" style="border-color: #F0EAE1; color: #1E1915;">
                        @foreach($productAnalytics['all'] as $p)
                            <tr class="hover:bg-[#FAF7F2]">
                                <td class="py-3 px-2 flex items-center gap-2">
                                    <img src="{{ $p['image'] }}" class="w-8 h-8 rounded-lg object-cover border shrink-0" style="border-color: #E8DECB;">
                                    <span class="font-bold truncate max-w-40 uppercase">{{ $p['name'] }}</span>
                                </td>
                                <td class="py-3 px-2">{{ number_format($p['views']) }}</td>
                                <td class="py-3 px-2">{{ number_format($p['addToCart']) }}</td>
                                <td class="py-3 px-2">{{ number_format($p['wishlist']) }}</td>
                                <td class="py-3 px-2 font-bold">{{ number_format($p['unitsSold']) }}</td>
                                <td class="py-3 px-2 font-bold" style="color: #C49520;">₱{{ number_format($p['revenue']) }}</td>
                                <td class="py-3 px-2">{{ $p['conversionRate'] }}%</td>
                                <td class="py-3 px-2 font-bold" style="color: #A16D19;">{{ $p['rating'] }} ★</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    {{-- TAB 4: CUSTOMER ANALYTICS --}}
    <div x-show="activeTab === 'customers'" class="space-y-6">
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-3 sm:gap-6">
            <div class="p-4 sm:p-6 rounded-2xl shadow-2xs" style="background: #FFFFFF; border: 1px solid #ECE3D2;">
                <div class="text-[9px] sm:text-[10px] font-bold uppercase tracking-widest mb-1" style="color: #8C827A;">Total Customers</div>
                <div class="text-lg sm:text-2xl font-black font-sans" style="color: #1E1915;">{{ number_format($customerAnalytics['totalCustomers']) }}</div>
            </div>

            <div class="p-4 sm:p-6 rounded-2xl shadow-2xs" style="background: #FFFFFF; border: 1px solid #ECE3D2;">
                <div class="text-[9px] sm:text-[10px] font-bold uppercase tracking-widest mb-1" style="color: #8C827A;">Repeat Purchase Rate</div>
                <div class="text-lg sm:text-2xl font-black font-sans text-purple-700">{{ $customerAnalytics['repeatRate'] }}%</div>
            </div>

            <div class="p-4 sm:p-6 rounded-2xl shadow-2xs" style="background: #FFFFFF; border: 1px solid #ECE3D2;">
                <div class="text-[9px] sm:text-[10px] font-bold uppercase tracking-widest mb-1" style="color: #8C827A;">Retention Rate</div>
                <div class="text-lg sm:text-2xl font-black font-sans text-blue-700">{{ $customerAnalytics['retentionRate'] }}%</div>
            </div>

            <div class="p-4 sm:p-6 rounded-2xl shadow-2xs" style="background: #FFFFFF; border: 1px solid #ECE3D2;">
                <div class="text-[9px] sm:text-[10px] font-bold uppercase tracking-widest mb-1" style="color: #8C827A;">Avg Spend / Customer</div>
                <div class="text-lg sm:text-2xl font-black font-sans" style="color: #C49520;">₱{{ number_format($customerAnalytics['avgCustomerSpend'], 2) }}</div>
            </div>
        </div>

        {{-- E-Commerce Customer Behavior Funnel --}}
        <div class="p-5 sm:p-6 rounded-3xl shadow-2xs space-y-4" style="background: #FFFFFF; border: 1px solid #ECE3D2;">
            <h3 class="font-serif text-xs sm:text-sm font-bold uppercase tracking-wider" style="color: #1E1915;">Customer Conversion Funnel</h3>
            <div class="space-y-3.5">
                <div>
                    <div class="flex justify-between text-xs font-bold mb-1 font-sans" style="color: #1E1915;">
                        <span>1. Product Views</span>
                        <span>{{ number_format($customerAnalytics['funnel']['views']) }} Views</span>
                    </div>
                    <div class="w-full h-3 rounded-full overflow-hidden" style="background: #FAF7F2;">
                        <div class="h-full rounded-full" style="width: 100%; background: #3B82F6;"></div>
                    </div>
                </div>

                <div>
                    <div class="flex justify-between text-xs font-bold mb-1 font-sans" style="color: #1E1915;">
                        <span>2. Add to Cart</span>
                        <span>{{ number_format($customerAnalytics['funnel']['addToCart']) }} Events</span>
                    </div>
                    <div class="w-full h-3 rounded-full overflow-hidden" style="background: #FAF7F2;">
                        <div class="h-full rounded-full js-width" style="background: #8B5CF6;" data-width="{{ min(100, round(($customerAnalytics['funnel']['addToCart'] / max($customerAnalytics['funnel']['views'], 1)) * 100)) }}"></div>
                    </div>
                </div>

                <div>
                    <div class="flex justify-between text-xs font-bold mb-1 font-sans" style="color: #1E1915;">
                        <span>3. Saved to Wishlist</span>
                        <span>{{ number_format($customerAnalytics['funnel']['wishlist']) }} Saves</span>
                    </div>
                    <div class="w-full h-3 rounded-full overflow-hidden" style="background: #FAF7F2;">
                        <div class="h-full rounded-full js-width" style="background: #F59E0B;" data-width="{{ min(100, round(($customerAnalytics['funnel']['wishlist'] / max($customerAnalytics['funnel']['views'], 1)) * 100)) }}"></div>
                    </div>
                </div>

                <div>
                    <div class="flex justify-between text-xs font-bold mb-1 font-sans" style="color: #1E1915;">
                        <span>4. Checkout Initiated</span>
                        <span>{{ number_format($customerAnalytics['funnel']['checkout']) }} Checkouts</span>
                    </div>
                    <div class="w-full h-3 rounded-full overflow-hidden" style="background: #FAF7F2;">
                        <div class="h-full rounded-full js-width" style="background: #10B981;" data-width="{{ min(100, round(($customerAnalytics['funnel']['checkout'] / max($customerAnalytics['funnel']['views'], 1)) * 100)) }}"></div>
                    </div>
                </div>

                <div>
                    <div class="flex justify-between text-xs font-bold mb-1 font-sans" style="color: #1E1915;">
                        <span>5. Completed Purchases</span>
                        <span>{{ number_format($customerAnalytics['funnel']['completed']) }} Orders</span>
                    </div>
                    <div class="w-full h-3 rounded-full overflow-hidden" style="background: #FAF7F2;">
                        <div class="h-full rounded-full js-width" style="background: #C49520;" data-width="{{ min(100, round(($customerAnalytics['funnel']['completed'] / max($customerAnalytics['funnel']['views'], 1)) * 100)) }}"></div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Top Customer Lifetime Value Leaderboard --}}
        <div class="p-5 sm:p-6 rounded-3xl shadow-2xs space-y-4" style="background: #FFFFFF; border: 1px solid #ECE3D2;">
            <h3 class="font-serif text-xs sm:text-sm font-bold uppercase tracking-wider" style="color: #1E1915;">Top Customers (Lifetime Value)</h3>
            <div class="overflow-x-auto no-scrollbar">
                <table class="w-full text-left border-collapse min-w-100">
                    <thead>
                        <tr class="border-b text-[9px] font-bold uppercase tracking-wider" style="border-color: #E8DECB; color: #8C827A;">
                            <th class="py-3 px-2">Customer</th>
                            <th class="py-3 px-2">Email</th>
                            <th class="py-3 px-2">Orders Placed</th>
                            <th class="py-3 px-2">Total Spent</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y text-xs font-medium" style="border-color: #F0EAE1; color: #1E1915;">
                        @forelse($customerAnalytics['topCustomers'] as $cust)
                            <tr class="hover:bg-[#FAF7F2]">
                                <td class="py-3 px-2 font-bold uppercase">{{ $cust['name'] }}</td>
                                <td class="py-3 px-2 font-sans" style="color: #766C60;">{{ $cust['email'] }}</td>
                                <td class="py-3 px-2 font-bold font-sans">{{ $cust['orderCount'] }} orders</td>
                                <td class="py-3 px-2 font-bold font-sans" style="color: #C49520;">₱{{ number_format($cust['totalSpent'], 2) }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="py-6 text-center text-xs italic" style="color: #8C827A;">No customer orders recorded yet.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    {{-- TAB 5: SALES BY CATEGORY --}}
    <div x-show="activeTab === 'category'" class="space-y-6">
        <div class="p-5 sm:p-6 rounded-3xl shadow-2xs space-y-4" style="background: #FFFFFF; border: 1px solid #ECE3D2;">
            <h3 class="font-serif text-xs sm:text-sm font-bold uppercase tracking-wider" style="color: #1E1915;">Barong & Filipiniana Category Demand</h3>
            
            <div class="space-y-4">
                @forelse($categorySales as $cat)
                    <div class="p-4 rounded-2xl space-y-2" style="background: #FAF7F2; border: 1px solid #E8DECB;">
                        <div class="flex items-center justify-between text-xs font-bold font-sans" style="color: #1E1915;">
                            <span>{{ $cat->category_name }}</span>
                            <span style="color: #C49520;">₱{{ number_format($cat->revenue, 2) }} ({{ $cat->percentage }}%)</span>
                        </div>
                        <div class="w-full h-2.5 rounded-full overflow-hidden" style="background: #ECE3D2;">
                            <div class="h-full rounded-full js-width" style="background: #C49520;" data-width="{{ $cat->percentage }}"></div>
                        </div>
                        <div class="text-[9px] font-bold uppercase font-sans" style="color: #8C827A;">{{ number_format($cat->units_sold) }} Barong Items Sold</div>
                    </div>
                @empty
                    <div class="p-6 text-center text-xs italic" style="color: #8C827A;">No category sales data recorded yet.</div>
                @endforelse
            </div>
        </div>
    </div>

    {{-- TAB 6: FINANCIAL ANALYTICS --}}
    <div x-show="activeTab === 'financials'" class="space-y-6">
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-3 sm:gap-6">
            <div class="p-4 sm:p-6 rounded-2xl shadow-2xs" style="background: #FFFFFF; border: 1px solid #ECE3D2;">
                <div class="text-[9px] sm:text-[10px] font-bold uppercase tracking-widest mb-1" style="color: #8C827A;">Gross Sales</div>
                <div class="text-lg sm:text-2xl font-black font-sans" style="color: #1E1915;">₱{{ number_format($financialAnalytics['grossSales'], 2) }}</div>
            </div>

            <div class="p-4 sm:p-6 rounded-2xl shadow-2xs" style="background: #FFFFFF; border: 1px solid #ECE3D2;">
                <div class="text-[9px] sm:text-[10px] font-bold uppercase tracking-widest mb-1" style="color: #8C827A;">Platform Commission (10%)</div>
                <div class="text-lg sm:text-2xl font-black font-sans" style="color: #A16D19;">₱{{ number_format($financialAnalytics['commissionFee'], 2) }}</div>
            </div>

            <div class="p-4 sm:p-6 rounded-2xl shadow-2xs" style="background: #FFFFFF; border: 1px solid #ECE3D2;">
                <div class="text-[9px] sm:text-[10px] font-bold uppercase tracking-widest mb-1" style="color: #8C827A;">Discounts & Refunds</div>
                <div class="text-lg sm:text-2xl font-black font-sans text-rose-600">₱{{ number_format($financialAnalytics['discounts'] + $financialAnalytics['refunds'], 2) }}</div>
            </div>

            <div class="p-4 sm:p-6 rounded-2xl shadow-2xs" style="background: #FFFFFF; border: 1px solid #ECE3D2;">
                <div class="text-[9px] sm:text-[10px] font-bold uppercase tracking-widest mb-1" style="color: #8C827A;">Net Seller Earnings</div>
                <div class="text-lg sm:text-2xl font-black font-sans text-emerald-600">₱{{ number_format($financialAnalytics['sellerEarnings'], 2) }}</div>
            </div>
        </div>

        {{-- Detailed Financial Settlement Statement --}}
        <div class="p-5 sm:p-6 rounded-3xl shadow-2xs space-y-4" style="background: #FFFFFF; border: 1px solid #ECE3D2;">
            <h3 class="font-serif text-xs sm:text-sm font-bold uppercase tracking-wider" style="color: #1E1915;">Financial Settlement Breakdown</h3>
            <div class="divide-y text-xs font-medium" style="border-color: #F0EAE1; color: #1E1915;">
                <div class="py-3 flex justify-between font-sans">
                    <span>Gross Item Sales</span>
                    <span class="font-bold">₱{{ number_format($financialAnalytics['grossSales'], 2) }}</span>
                </div>
                <div class="py-3 flex justify-between font-sans" style="color: #A16D19;">
                    <span>LumBarong Marketplace Commission Fee (10%)</span>
                    <span class="font-bold">- ₱{{ number_format($financialAnalytics['commissionFee'], 2) }}</span>
                </div>
                <div class="py-3 flex justify-between text-rose-600 font-sans">
                    <span>Discounts & Vouchers Applied</span>
                    <span class="font-bold">- ₱{{ number_format($financialAnalytics['discounts'], 2) }}</span>
                </div>
                <div class="py-3 flex justify-between text-rose-600 font-sans">
                    <span>Refunds / Returns</span>
                    <span class="font-bold">- ₱{{ number_format($financialAnalytics['refunds'], 2) }}</span>
                </div>
                <div class="py-3.5 flex justify-between border-t-2 text-sm font-black pt-4 font-sans" style="border-color: #1E1915;">
                    <span class="uppercase">Take-Home Seller Net Payout</span>
                    <span class="text-emerald-600 text-base font-bold">₱{{ number_format($financialAnalytics['sellerEarnings'], 2) }}</span>
                </div>
            </div>
        </div>
    </div>

    {{-- TAB 7: MARKETING ANALYTICS --}}
    <div x-show="activeTab === 'marketing'" class="space-y-6">
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
            <div class="p-4 sm:p-6 rounded-2xl shadow-2xs" style="background: #FFFFFF; border: 1px solid #ECE3D2;">
                <div class="text-[10px] font-bold uppercase tracking-widest mb-1" style="color: #8C827A;">Listings On Sale</div>
                <div class="text-xl font-black font-sans" style="color: #1E1915;">{{ number_format($marketingAnalytics['discountedProductsCount']) }} products</div>
            </div>

            <div class="p-4 sm:p-6 rounded-2xl shadow-2xs" style="background: #FFFFFF; border: 1px solid #ECE3D2;">
                <div class="text-[10px] font-bold uppercase tracking-widest mb-1" style="color: #8C827A;">Promotional Items Sold</div>
                <div class="text-xl font-black font-sans text-purple-700">{{ number_format($marketingAnalytics['saleItemsSold']) }} pcs</div>
            </div>

            <div class="p-4 sm:p-6 rounded-2xl shadow-2xs" style="background: #FFFFFF; border: 1px solid #ECE3D2;">
                <div class="text-[10px] font-bold uppercase tracking-widest mb-1" style="color: #8C827A;">Promotional Sales Revenue</div>
                <div class="text-xl font-black font-sans" style="color: #C49520;">₱{{ number_format($marketingAnalytics['saleRevenue'], 2) }}</div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    document.querySelectorAll('.js-width').forEach(function(el) {
        const w = el.getAttribute('data-width');
        if (w) {
            el.style.width = w + '%';
        }
    });
});
</script>
@endsection
