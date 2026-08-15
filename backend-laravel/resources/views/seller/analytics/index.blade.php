@extends('layouts.seller')

@section('content')
<div x-data="{ activeTab: 'sales' }" class="space-y-6 sm:space-y-8">
    {{-- Header & Date Filter Toolbar --}}
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <div class="flex items-center gap-2 mb-1">
                <span class="text-[10px] font-bold text-[#C0422A] uppercase tracking-[0.2em]">Deep Shop Insights</span>
                <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 bg-emerald-50 border border-emerald-100 rounded-full text-[9px] font-black uppercase text-emerald-700 tracking-wider">
                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 animate-pulse"></span> Live Real-Time
                </span>
            </div>
            <h1 class="font-serif text-2xl sm:text-3xl font-bold text-black uppercase">Seller <span class="text-[#C0422A] italic lowercase">analytics</span></h1>
        </div>

        {{-- Date Filter Form --}}
        <form method="GET" action="{{ route('seller.analytics') }}" x-data="{ selectedPreset: '{{ $filters['preset'] ?? 'all_time' }}' }" class="flex items-center gap-2">
            <div class="relative flex items-center bg-white border border-gray-200 rounded-2xl px-4 py-2 shadow-xs hover:border-gray-300 transition-all">
                <svg class="w-4 h-4 text-gray-400 shrink-0 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                </svg>
                <select name="date_preset" x-model="selectedPreset" @change="$el.form.submit()" class="bg-transparent text-xs font-bold text-black outline-none cursor-pointer pr-4">
                    <option value="all_time" {{ in_array(($filters['preset'] ?? ''), ['all_time', '']) ? 'selected' : '' }}>All Time</option>
                    <option value="today" {{ ($filters['preset'] ?? '') == 'today' ? 'selected' : '' }}>Today</option>
                    <option value="1_week" {{ in_array(($filters['preset'] ?? ''), ['1_week', 'last_7_days']) ? 'selected' : '' }}>1 Week</option>
                    <option value="1_month" {{ in_array(($filters['preset'] ?? ''), ['1_month', 'last_30_days']) ? 'selected' : '' }}>1 Month</option>
                    <option value="1_year" {{ in_array(($filters['preset'] ?? ''), ['1_year', 'last_365_days']) ? 'selected' : '' }}>1 Year</option>
                </select>
            </div>
            @if(($filters['preset'] ?? 'all_time') !== 'all_time')
                <a href="{{ route('seller.analytics') }}" class="text-[10px] font-bold text-gray-400 hover:text-red-600 uppercase tracking-widest px-2">Reset ✕</a>
            @endif
        </form>
    </div>

    {{-- Interactive Module Tabs --}}
    <div class="overflow-x-auto no-scrollbar -mx-4 px-4 sm:mx-0 sm:px-0">
        <div class="flex items-center gap-2 border-b border-gray-200 pb-2 min-w-max">
            <button @click="activeTab = 'sales'" :class="activeTab === 'sales' ? 'bg-[#C0422A] text-white shadow-sm' : 'bg-white text-gray-600 hover:bg-gray-100'" class="px-4 py-2 rounded-xl text-xs font-bold transition-all flex items-center gap-2">
                <span>💰 Sales Analytics</span>
            </button>
            <button @click="activeTab = 'orders'" :class="activeTab === 'orders' ? 'bg-[#C0422A] text-white shadow-sm' : 'bg-white text-gray-600 hover:bg-gray-100'" class="px-4 py-2 rounded-xl text-xs font-bold transition-all flex items-center gap-2">
                <span>📦 Order Analytics</span>
            </button>
            <button @click="activeTab = 'products'" :class="activeTab === 'products' ? 'bg-[#C0422A] text-white shadow-sm' : 'bg-white text-gray-600 hover:bg-gray-100'" class="px-4 py-2 rounded-xl text-xs font-bold transition-all flex items-center gap-2">
                <span>🛍️ Product Analytics</span>
            </button>
            <button @click="activeTab = 'customers'" :class="activeTab === 'customers' ? 'bg-[#C0422A] text-white shadow-sm' : 'bg-white text-gray-600 hover:bg-gray-100'" class="px-4 py-2 rounded-xl text-xs font-bold transition-all flex items-center gap-2">
                <span>👥 Customer Analytics</span>
            </button>
            <button @click="activeTab = 'category'" :class="activeTab === 'category' ? 'bg-[#C0422A] text-white shadow-sm' : 'bg-white text-gray-600 hover:bg-gray-100'" class="px-4 py-2 rounded-xl text-xs font-bold transition-all flex items-center gap-2">
                <span>🏷️ Sales by Category</span>
            </button>
            <button @click="activeTab = 'financials'" :class="activeTab === 'financials' ? 'bg-[#C0422A] text-white shadow-sm' : 'bg-white text-gray-600 hover:bg-gray-100'" class="px-4 py-2 rounded-xl text-xs font-bold transition-all flex items-center gap-2">
                <span>💵 Financial Analytics</span>
            </button>
            <button @click="activeTab = 'marketing'" :class="activeTab === 'marketing' ? 'bg-[#C0422A] text-white shadow-sm' : 'bg-white text-gray-600 hover:bg-gray-100'" class="px-4 py-2 rounded-xl text-xs font-bold transition-all flex items-center gap-2">
                <span>🏷️ Marketing Analytics</span>
            </button>
        </div>
    </div>

    {{-- TAB 1: SALES ANALYTICS --}}
    <div x-show="activeTab === 'sales'" class="space-y-6">
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-3 sm:gap-6">
            <div class="bg-white p-4 sm:p-6 rounded-2xl border border-gray-100 shadow-xs">
                <div class="text-[9px] sm:text-[10px] font-bold uppercase tracking-widest text-gray-400 mb-1">Total Sales</div>
                <div class="text-lg sm:text-2xl font-black text-[#C0422A]">₱{{ number_format($salesAnalytics['totalSales'], 2) }}</div>
                <div class="text-[9px] font-bold text-gray-400 mt-1 uppercase">Net Sales: ₱{{ number_format($salesAnalytics['netSales'], 2) }}</div>
            </div>

            <div class="bg-white p-4 sm:p-6 rounded-2xl border border-gray-100 shadow-xs">
                <div class="text-[9px] sm:text-[10px] font-bold uppercase tracking-widest text-gray-400 mb-1">Average Order Value</div>
                <div class="text-lg sm:text-2xl font-black text-black">₱{{ number_format($salesAnalytics['averageOrderValue'], 2) }}</div>
                <div class="text-[9px] font-bold text-gray-400 mt-1 uppercase">Per completed order</div>
            </div>

            <div class="bg-white p-4 sm:p-6 rounded-2xl border border-gray-100 shadow-xs">
                <div class="text-[9px] sm:text-[10px] font-bold uppercase tracking-widest text-gray-400 mb-1">Total Items Sold</div>
                <div class="text-lg sm:text-2xl font-black text-black">{{ number_format($salesAnalytics['totalItemsSold']) }} pcs</div>
                <div class="text-[9px] font-bold text-gray-400 mt-1 uppercase">Heritage barong pieces</div>
            </div>

            <div class="bg-white p-4 sm:p-6 rounded-2xl border border-gray-100 shadow-xs">
                <div class="text-[9px] sm:text-[10px] font-bold uppercase tracking-widest text-gray-400 mb-1">Monthly Growth</div>
                <div class="text-lg sm:text-2xl font-black {{ $salesAnalytics['monthGrowthPct'] >= 0 ? 'text-green-600' : 'text-red-600' }}">
                    {{ $salesAnalytics['monthGrowthPct'] >= 0 ? '+' : '' }}{{ $salesAnalytics['monthGrowthPct'] }}%
                </div>
                <div class="text-[9px] font-bold text-gray-400 mt-1 uppercase">This Month vs Last Month</div>
            </div>
        </div>

        {{-- Sales Comparisons Matrix --}}
        <div class="bg-white p-5 sm:p-6 rounded-3xl border border-gray-100 shadow-xs space-y-4">
            <h3 class="text-xs sm:text-sm font-bold text-black uppercase">Sales Comparison Analysis</h3>
            
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                <div class="p-4 bg-gray-50/60 rounded-2xl border border-gray-100">
                    <div class="text-[10px] font-bold text-gray-400 uppercase tracking-widest mb-1">This Month vs Last Month</div>
                    <div class="text-base font-black text-black">₱{{ number_format($salesAnalytics['thisMonthSales'], 2) }}</div>
                    <div class="text-[10px] text-gray-500 mt-0.5">Last Month: ₱{{ number_format($salesAnalytics['lastMonthSales'], 2) }}</div>
                    <span class="inline-block mt-2 px-2 py-0.5 rounded-full text-[9px] font-black uppercase {{ $salesAnalytics['monthGrowthPct'] >= 0 ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700' }}">
                        {{ $salesAnalytics['monthGrowthPct'] >= 0 ? '▲' : '▼' }} {{ abs($salesAnalytics['monthGrowthPct']) }}%
                    </span>
                </div>

                <div class="p-4 bg-gray-50/60 rounded-2xl border border-gray-100">
                    <div class="text-[10px] font-bold text-gray-400 uppercase tracking-widest mb-1">This Week vs Last Week</div>
                    <div class="text-base font-black text-black">₱{{ number_format($salesAnalytics['thisWeekSales'], 2) }}</div>
                    <div class="text-[10px] text-gray-500 mt-0.5">Last Week: ₱{{ number_format($salesAnalytics['prevWeekSales'], 2) }}</div>
                    <span class="inline-block mt-2 px-2 py-0.5 rounded-full text-[9px] font-black uppercase {{ $salesAnalytics['weekGrowthPct'] >= 0 ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700' }}">
                        {{ $salesAnalytics['weekGrowthPct'] >= 0 ? '▲' : '▼' }} {{ abs($salesAnalytics['weekGrowthPct']) }}%
                    </span>
                </div>

                <div class="p-4 bg-gray-50/60 rounded-2xl border border-gray-100">
                    <div class="text-[10px] font-bold text-gray-400 uppercase tracking-widest mb-1">This Year vs Last Year</div>
                    <div class="text-base font-black text-black">₱{{ number_format($salesAnalytics['thisYearSales'], 2) }}</div>
                    <div class="text-[10px] text-gray-500 mt-0.5">Last Year: ₱{{ number_format($salesAnalytics['prevYearSales'], 2) }}</div>
                    <span class="inline-block mt-2 px-2 py-0.5 rounded-full text-[9px] font-black uppercase {{ $salesAnalytics['yearGrowthPct'] >= 0 ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700' }}">
                        {{ $salesAnalytics['yearGrowthPct'] >= 0 ? '▲' : '▼' }} {{ abs($salesAnalytics['yearGrowthPct']) }}%
                    </span>
                </div>
            </div>
        </div>

        {{-- Sales Trend Visualizer Chart --}}
        <div class="bg-white p-5 sm:p-6 rounded-3xl border border-gray-100 shadow-xs space-y-4">
            <h3 class="text-xs sm:text-sm font-bold text-black uppercase">Sales Trend Overview</h3>
            <div class="overflow-x-auto">
                <div class="flex items-end justify-between gap-3 h-44 min-w-80 pt-4">
                    @foreach($salesTrendChart['points'] as $pt)
                        <div class="flex-1 flex flex-col items-center gap-2">
                            @php 
                                $heightPct = $salesTrendChart['max'] > 0 
                                    ? ($pt['revenue'] > 0 ? max(8, round(($pt['revenue'] / $salesTrendChart['max']) * 100)) : 4) 
                                    : 4; 
                            @endphp
                            <div class="w-full max-w-12 bg-gray-100 rounded-t-xl relative overflow-hidden h-32">
                                <div class="absolute inset-x-0 bottom-0 bg-[#C0422A] rounded-t-xl transition-all duration-500" style="height: {{ $heightPct }}%;"></div>
                            </div>
                            <div class="text-center">
                                <div class="text-[9px] font-black text-gray-400 uppercase">{{ $pt['label'] }}</div>
                                <div class="text-[9px] font-bold text-black">₱{{ number_format($pt['revenue']) }}</div>
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
            <div class="bg-white p-4 sm:p-6 rounded-2xl border border-gray-100 shadow-xs">
                <div class="text-[9px] sm:text-[10px] font-bold uppercase tracking-widest text-gray-400 mb-1">Total Orders</div>
                <div class="text-lg sm:text-2xl font-black text-black">{{ number_format($orderAnalytics['stats']['total']) }}</div>
            </div>

            <div class="bg-white p-4 sm:p-6 rounded-2xl border border-gray-100 shadow-xs">
                <div class="text-[9px] sm:text-[10px] font-bold uppercase tracking-widest text-gray-400 mb-1">Completion Rate</div>
                <div class="text-lg sm:text-2xl font-black text-green-600">{{ $orderAnalytics['completionRate'] }}%</div>
            </div>

            <div class="bg-white p-4 sm:p-6 rounded-2xl border border-gray-100 shadow-xs">
                <div class="text-[9px] sm:text-[10px] font-bold uppercase tracking-widest text-gray-400 mb-1">To Ship / Processing</div>
                <div class="text-lg sm:text-2xl font-black text-blue-600">{{ number_format($orderAnalytics['stats']['toShip']) }}</div>
            </div>

            <div class="bg-white p-4 sm:p-6 rounded-2xl border border-gray-100 shadow-xs">
                <div class="text-[9px] sm:text-[10px] font-bold uppercase tracking-widest text-gray-400 mb-1">Avg Processing Time</div>
                <div class="text-lg sm:text-2xl font-black text-black">~{{ $orderAnalytics['avgProcessingTimeHours'] }} hrs</div>
            </div>
        </div>

        {{-- Order Status Breakdown --}}
        <div class="bg-white p-5 sm:p-6 rounded-3xl border border-gray-100 shadow-xs space-y-4">
            <h3 class="text-xs sm:text-sm font-bold text-black uppercase">Order Status Distribution</h3>
            <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
                <div class="p-4 bg-amber-50/60 rounded-2xl border border-amber-100">
                    <div class="text-[10px] font-bold text-amber-700 uppercase tracking-widest">Pending Orders</div>
                    <div class="text-xl font-black text-amber-900 mt-1">{{ number_format($orderAnalytics['stats']['pending']) }}</div>
                </div>

                <div class="p-4 bg-blue-50/60 rounded-2xl border border-blue-100">
                    <div class="text-[10px] font-bold text-blue-700 uppercase tracking-widest">To Ship</div>
                    <div class="text-xl font-black text-blue-900 mt-1">{{ number_format($orderAnalytics['stats']['toShip']) }}</div>
                </div>

                <div class="p-4 bg-green-50/60 rounded-2xl border border-green-100">
                    <div class="text-[10px] font-bold text-green-700 uppercase tracking-widest">Delivered / Completed</div>
                    <div class="text-xl font-black text-green-900 mt-1">{{ number_format($orderAnalytics['stats']['completed']) }}</div>
                </div>

                <div class="p-4 bg-red-50/60 rounded-2xl border border-red-100">
                    <div class="text-[10px] font-bold text-red-700 uppercase tracking-widest">Cancelled</div>
                    <div class="text-xl font-black text-red-900 mt-1">{{ number_format($orderAnalytics['stats']['cancelled']) }}</div>
                </div>
            </div>
        </div>
    </div>

    {{-- TAB 3: PRODUCT ANALYTICS --}}
    <div x-show="activeTab === 'products'" class="space-y-6">
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <!-- Best Selling Products -->
            <div class="bg-white p-5 sm:p-6 rounded-3xl border border-gray-100 shadow-xs space-y-4">
                <h3 class="text-xs sm:text-sm font-bold text-black uppercase flex items-center justify-between">
                    <span>🔥 Best Selling Products</span>
                    <span class="text-[9px] text-[#C0422A] uppercase font-bold">Top Revenue</span>
                </h3>
                <div class="space-y-3">
                    @foreach($productAnalytics['bestSelling'] as $prod)
                        <div class="flex items-center justify-between p-3 bg-gray-50/50 rounded-2xl border border-gray-100 gap-3">
                            <div class="flex items-center gap-3 min-w-0">
                                <img src="{{ $prod['image'] }}" class="w-10 h-10 rounded-xl object-cover border border-gray-100 shrink-0">
                                <div class="min-w-0">
                                    <div class="text-xs font-bold text-black truncate uppercase">{{ $prod['name'] }}</div>
                                    <div class="text-[9px] text-gray-400 font-bold">Stock: {{ $prod['stock'] }} | Rating: {{ $prod['rating'] }} ★</div>
                                </div>
                            </div>
                            <div class="text-right shrink-0">
                                <div class="text-xs font-black text-[#C0422A]">₱{{ number_format($prod['revenue']) }}</div>
                                <div class="text-[9px] font-bold text-gray-500 uppercase">{{ $prod['unitsSold'] }} Sold</div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>

            <!-- Most Viewed Products -->
            <div class="bg-white p-5 sm:p-6 rounded-3xl border border-gray-100 shadow-xs space-y-4">
                <h3 class="text-xs sm:text-sm font-bold text-black uppercase flex items-center justify-between">
                    <span>👁️ Most Viewed Products</span>
                    <span class="text-[9px] text-blue-600 uppercase font-bold">High Traffic</span>
                </h3>
                <div class="space-y-3">
                    @foreach($productAnalytics['mostViewed'] as $prod)
                        <div class="flex items-center justify-between p-3 bg-gray-50/50 rounded-2xl border border-gray-100 gap-3">
                            <div class="flex items-center gap-3 min-w-0">
                                <img src="{{ $prod['image'] }}" class="w-10 h-10 rounded-xl object-cover border border-gray-100 shrink-0">
                                <div class="min-w-0">
                                    <div class="text-xs font-bold text-black truncate uppercase">{{ $prod['name'] }}</div>
                                    <div class="text-[9px] text-gray-400 font-bold">Conversion: {{ $prod['conversionRate'] }}%</div>
                                </div>
                            </div>
                            <div class="text-right shrink-0">
                                <div class="text-xs font-black text-black">{{ number_format($prod['views']) }} Views</div>
                                <div class="text-[9px] font-bold text-gray-500 uppercase">{{ $prod['orders'] }} Orders</div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>

        {{-- Granular Per-Product Performance Table --}}
        <div class="bg-white p-5 sm:p-6 rounded-3xl border border-gray-100 shadow-xs space-y-4">
            <h3 class="text-xs sm:text-sm font-bold text-black uppercase">Per-Product Performance Catalogue</h3>
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse min-w-140">
                    <thead>
                        <tr class="border-b border-gray-100 text-[9px] font-black uppercase text-gray-400 tracking-wider">
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
                    <tbody class="divide-y divide-gray-100 text-xs font-medium text-black">
                        @foreach($productAnalytics['all'] as $p)
                            <tr class="hover:bg-gray-50/50">
                                <td class="py-3 px-2 flex items-center gap-2">
                                    <img src="{{ $p['image'] }}" class="w-8 h-8 rounded-lg object-cover border border-gray-100 shrink-0">
                                    <span class="font-bold truncate max-w-40 uppercase">{{ $p['name'] }}</span>
                                </td>
                                <td class="py-3 px-2">{{ number_format($p['views']) }}</td>
                                <td class="py-3 px-2">{{ number_format($p['addToCart']) }}</td>
                                <td class="py-3 px-2">{{ number_format($p['wishlist']) }}</td>
                                <td class="py-3 px-2 font-bold">{{ number_format($p['unitsSold']) }}</td>
                                <td class="py-3 px-2 font-black text-[#C0422A]">₱{{ number_format($p['revenue']) }}</td>
                                <td class="py-3 px-2">{{ $p['conversionRate'] }}%</td>
                                <td class="py-3 px-2 text-amber-500 font-bold">{{ $p['rating'] }} ★</td>
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
            <div class="bg-white p-4 sm:p-6 rounded-2xl border border-gray-100 shadow-xs">
                <div class="text-[9px] sm:text-[10px] font-bold uppercase tracking-widest text-gray-400 mb-1">Total Customers</div>
                <div class="text-lg sm:text-2xl font-black text-black">{{ number_format($customerAnalytics['totalCustomers']) }}</div>
            </div>

            <div class="bg-white p-4 sm:p-6 rounded-2xl border border-gray-100 shadow-xs">
                <div class="text-[9px] sm:text-[10px] font-bold uppercase tracking-widest text-gray-400 mb-1">Repeat Purchase Rate</div>
                <div class="text-lg sm:text-2xl font-black text-purple-600">{{ $customerAnalytics['repeatRate'] }}%</div>
            </div>

            <div class="bg-white p-4 sm:p-6 rounded-2xl border border-gray-100 shadow-xs">
                <div class="text-[9px] sm:text-[10px] font-bold uppercase tracking-widest text-gray-400 mb-1">Retention Rate</div>
                <div class="text-lg sm:text-2xl font-black text-blue-600">{{ $customerAnalytics['retentionRate'] }}%</div>
            </div>

            <div class="bg-white p-4 sm:p-6 rounded-2xl border border-gray-100 shadow-xs">
                <div class="text-[9px] sm:text-[10px] font-bold uppercase tracking-widest text-gray-400 mb-1">Avg Spend / Customer</div>
                <div class="text-lg sm:text-2xl font-black text-[#C0422A]">₱{{ number_format($customerAnalytics['avgCustomerSpend'], 2) }}</div>
            </div>
        </div>

        {{-- E-Commerce Customer Behavior Funnel --}}
        <div class="bg-white p-5 sm:p-6 rounded-3xl border border-gray-100 shadow-xs space-y-4">
            <h3 class="text-xs sm:text-sm font-bold text-black uppercase">Customer Behavior Conversion Funnel</h3>
            <div class="space-y-3">
                <div>
                    <div class="flex justify-between text-xs font-bold text-gray-700 mb-1">
                        <span>1. Product Views</span>
                        <span>{{ number_format($customerAnalytics['funnel']['views']) }} Views</span>
                    </div>
                    <div class="w-full h-3 bg-gray-100 rounded-full overflow-hidden">
                        <div class="h-full bg-blue-500 rounded-full" style="width: 100%;"></div>
                    </div>
                </div>

                <div>
                    <div class="flex justify-between text-xs font-bold text-gray-700 mb-1">
                        <span>2. Add to Cart</span>
                        <span>{{ number_format($customerAnalytics['funnel']['addToCart']) }} Events</span>
                    </div>
                    <div class="w-full h-3 bg-gray-100 rounded-full overflow-hidden">
                        <div class="h-full bg-purple-500 rounded-full js-width" data-width="{{ min(100, round(($customerAnalytics['funnel']['addToCart'] / max($customerAnalytics['funnel']['views'], 1)) * 100)) }}"></div>
                    </div>
                </div>

                <div>
                    <div class="flex justify-between text-xs font-bold text-gray-700 mb-1">
                        <span>3. Saved to Wishlist</span>
                        <span>{{ number_format($customerAnalytics['funnel']['wishlist']) }} Saves</span>
                    </div>
                    <div class="w-full h-3 bg-gray-100 rounded-full overflow-hidden">
                        <div class="h-full bg-amber-500 rounded-full js-width" data-width="{{ min(100, round(($customerAnalytics['funnel']['wishlist'] / max($customerAnalytics['funnel']['views'], 1)) * 100)) }}"></div>
                    </div>
                </div>

                <div>
                    <div class="flex justify-between text-xs font-bold text-gray-700 mb-1">
                        <span>4. Checkout Initiated</span>
                        <span>{{ number_format($customerAnalytics['funnel']['checkout']) }} Checkouts</span>
                    </div>
                    <div class="w-full h-3 bg-gray-100 rounded-full overflow-hidden">
                        <div class="h-full bg-emerald-500 rounded-full js-width" data-width="{{ min(100, round(($customerAnalytics['funnel']['checkout'] / max($customerAnalytics['funnel']['views'], 1)) * 100)) }}"></div>
                    </div>
                </div>

                <div>
                    <div class="flex justify-between text-xs font-bold text-black mb-1">
                        <span>5. Completed Purchases</span>
                        <span>{{ number_format($customerAnalytics['funnel']['completed']) }} Orders</span>
                    </div>
                    <div class="w-full h-3 bg-gray-100 rounded-full overflow-hidden">
                        <div class="h-full bg-[#C0422A] rounded-full js-width" data-width="{{ min(100, round(($customerAnalytics['funnel']['completed'] / max($customerAnalytics['funnel']['views'], 1)) * 100)) }}"></div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Top Customer Lifetime Value Leaderboard --}}
        <div class="bg-white p-5 sm:p-6 rounded-3xl border border-gray-100 shadow-xs space-y-4">
            <h3 class="text-xs sm:text-sm font-bold text-black uppercase">Top Customers (Lifetime Value)</h3>
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse min-w-100">
                    <thead>
                        <tr class="border-b border-gray-100 text-[9px] font-black uppercase text-gray-400 tracking-wider">
                            <th class="py-3 px-2">Customer</th>
                            <th class="py-3 px-2">Email</th>
                            <th class="py-3 px-2">Orders Placed</th>
                            <th class="py-3 px-2">Total Spent</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 text-xs font-medium text-black">
                        @foreach($customerAnalytics['topCustomers'] as $cust)
                            <tr class="hover:bg-gray-50/50">
                                <td class="py-3 px-2 font-bold uppercase">{{ $cust['name'] }}</td>
                                <td class="py-3 px-2 text-gray-500">{{ $cust['email'] }}</td>
                                <td class="py-3 px-2 font-bold">{{ $cust['orderCount'] }} orders</td>
                                <td class="py-3 px-2 font-black text-[#C0422A]">₱{{ number_format($cust['totalSpent'], 2) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    {{-- TAB 5: SALES BY CATEGORY --}}
    <div x-show="activeTab === 'category'" class="space-y-6">
        <div class="bg-white p-5 sm:p-6 rounded-3xl border border-gray-100 shadow-xs space-y-4">
            <h3 class="text-xs sm:text-sm font-bold text-black uppercase">Barong & Filipiniana Category Demand</h3>
            
            <div class="space-y-4">
                @forelse($categorySales as $cat)
                    <div class="p-4 bg-gray-50/60 rounded-2xl border border-gray-100 space-y-2">
                        <div class="flex items-center justify-between text-xs font-bold text-black">
                            <span>{{ $cat->category_name }}</span>
                            <span class="text-[#C0422A]">₱{{ number_format($cat->revenue, 2) }} ({{ $cat->percentage }}%)</span>
                        </div>
                        <div class="w-full h-2.5 bg-gray-200 rounded-full overflow-hidden">
                            <div class="h-full bg-[#C0422A] rounded-full js-width" data-width="{{ $cat->percentage }}"></div>
                        </div>
                        <div class="text-[9px] font-bold text-gray-400 uppercase">{{ number_format($cat->units_sold) }} Barong Items Sold</div>
                    </div>
                @empty
                    <div class="p-6 text-center text-gray-400 text-xs italic">No category sales data recorded yet.</div>
                @endforelse
            </div>
        </div>
    </div>

    {{-- TAB 6: FINANCIAL ANALYTICS --}}
    <div x-show="activeTab === 'financials'" class="space-y-6">
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-3 sm:gap-6">
            <div class="bg-white p-4 sm:p-6 rounded-2xl border border-gray-100 shadow-xs">
                <div class="text-[9px] sm:text-[10px] font-bold uppercase tracking-widest text-gray-400 mb-1">Gross Sales</div>
                <div class="text-lg sm:text-2xl font-black text-black">₱{{ number_format($financialAnalytics['grossSales'], 2) }}</div>
            </div>

            <div class="bg-white p-4 sm:p-6 rounded-2xl border border-gray-100 shadow-xs">
                <div class="text-[9px] sm:text-[10px] font-bold uppercase tracking-widest text-gray-400 mb-1">Platform Commission (10%)</div>
                <div class="text-lg sm:text-2xl font-black text-amber-600">₱{{ number_format($financialAnalytics['commissionFee'], 2) }}</div>
            </div>

            <div class="bg-white p-4 sm:p-6 rounded-2xl border border-gray-100 shadow-xs">
                <div class="text-[9px] sm:text-[10px] font-bold uppercase tracking-widest text-gray-400 mb-1">Discounts & Refunds</div>
                <div class="text-lg sm:text-2xl font-black text-red-600">₱{{ number_format($financialAnalytics['discounts'] + $financialAnalytics['refunds'], 2) }}</div>
            </div>

            <div class="bg-white p-4 sm:p-6 rounded-2xl border border-gray-100 shadow-xs">
                <div class="text-[9px] sm:text-[10px] font-bold uppercase tracking-widest text-gray-400 mb-1">Net Seller Earnings</div>
                <div class="text-lg sm:text-2xl font-black text-green-600">₱{{ number_format($financialAnalytics['sellerEarnings'], 2) }}</div>
            </div>
        </div>

        {{-- Detailed Financial Settlement Statement --}}
        <div class="bg-white p-5 sm:p-6 rounded-3xl border border-gray-100 shadow-xs space-y-4">
            <h3 class="text-xs sm:text-sm font-bold text-black uppercase">Financial Settlement Breakdown</h3>
            <div class="divide-y divide-gray-100 text-xs font-medium text-black">
                <div class="py-3 flex justify-between">
                    <span>Gross Item Sales</span>
                    <span class="font-bold">₱{{ number_format($financialAnalytics['grossSales'], 2) }}</span>
                </div>
                <div class="py-3 flex justify-between text-amber-700">
                    <span>LumBarong Marketplace Commission Fee (10%)</span>
                    <span class="font-bold">- ₱{{ number_format($financialAnalytics['commissionFee'], 2) }}</span>
                </div>
                <div class="py-3 flex justify-between text-red-600">
                    <span>Discounts & Vouchers Applied</span>
                    <span class="font-bold">- ₱{{ number_format($financialAnalytics['discounts'], 2) }}</span>
                </div>
                <div class="py-3 flex justify-between text-red-600">
                    <span>Refunds / Returns</span>
                    <span class="font-bold">- ₱{{ number_format($financialAnalytics['refunds'], 2) }}</span>
                </div>
                <div class="py-3 flex justify-between border-t-2 border-gray-900 text-sm font-black pt-4">
                    <span class="uppercase">Take-Home Seller Net Payout</span>
                    <span class="text-green-600">₱{{ number_format($financialAnalytics['sellerEarnings'], 2) }}</span>
                </div>
            </div>
        </div>
    </div>

    {{-- TAB 7: MARKETING ANALYTICS --}}
    <div x-show="activeTab === 'marketing'" class="space-y-6">
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
            <div class="bg-white p-4 sm:p-6 rounded-2xl border border-gray-100 shadow-xs">
                <div class="text-[10px] font-bold uppercase tracking-widest text-gray-400 mb-1">Listings On Sale</div>
                <div class="text-xl font-black text-black">{{ number_format($marketingAnalytics['discountedProductsCount']) }} products</div>
            </div>

            <div class="bg-white p-4 sm:p-6 rounded-2xl border border-gray-100 shadow-xs">
                <div class="text-[10px] font-bold uppercase tracking-widest text-gray-400 mb-1">Promotional Items Sold</div>
                <div class="text-xl font-black text-purple-600">{{ number_format($marketingAnalytics['saleItemsSold']) }} pcs</div>
            </div>

            <div class="bg-white p-4 sm:p-6 rounded-2xl border border-gray-100 shadow-xs">
                <div class="text-[10px] font-bold uppercase tracking-widest text-gray-400 mb-1">Promotional Sales Revenue</div>
                <div class="text-xl font-black text-[#C0422A]">₱{{ number_format($marketingAnalytics['saleRevenue'], 2) }}</div>
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
    document.querySelectorAll('.js-width').forEach(function(el) {
        const w = el.getAttribute('data-width');
        if (w) {
            el.style.width = w + '%';
        }
    });
});
</script>
@endsection
