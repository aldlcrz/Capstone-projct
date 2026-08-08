@extends('layouts.seller')

@section('content')
<div class="space-y-8">
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
            <div class="text-[10px] font-bold text-[#C0422A] uppercase tracking-[0.2em] mb-1">Seller Performance</div>
            <h1 class="font-serif text-3xl font-bold text-black uppercase">Seller <span class="text-[#C0422A] italic lowercase">dashboard</span></h1>
        </div>
        <div class="flex items-center gap-3">
            <a href="{{ route('seller.export', request()->all()) }}" class="flex items-center gap-2 px-6 py-3 bg-[#3D2B1F] text-white rounded-xl text-[10px] font-bold uppercase tracking-widest hover:bg-[#C0422A] transition-all shadow-lg shadow-black/5">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                Export CSV
            </a>
        </div>
    </div>

    {{-- Date Filter Toolbar --}}
    <form method="GET" action="{{ route('seller.dashboard') }}" x-data="{ selectedPreset: '{{ $filters['preset'] ?? 'all_time' }}' }" class="bg-white p-4 rounded-2xl border border-gray-100 shadow-sm flex flex-wrap items-center justify-between gap-4">
        <div class="flex flex-wrap items-center gap-3">
            <div class="flex items-center gap-2">
                <svg class="w-4 h-4 text-[#C0422A]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                <span class="text-xs font-black uppercase tracking-wider text-black">Filter Date:</span>
            </div>

            {{-- Date Presets --}}
            <select name="date_preset" x-model="selectedPreset" @change="$el.form.submit()" class="px-3.5 py-2 bg-gray-50 border border-gray-200 rounded-xl text-xs font-bold text-gray-800 outline-none focus:border-[#C0422A] cursor-pointer">
                <option value="all_time" {{ in_array(($filters['preset'] ?? ''), ['all_time', '']) ? 'selected' : '' }}>All Time</option>
                <option value="today" {{ ($filters['preset'] ?? '') == 'today' ? 'selected' : '' }}>Today</option>
                <option value="1_week" {{ in_array(($filters['preset'] ?? ''), ['1_week', 'last_7_days']) ? 'selected' : '' }}>1 Week</option>
                <option value="1_month" {{ in_array(($filters['preset'] ?? ''), ['1_month', 'last_30_days', 'this_month']) ? 'selected' : '' }}>1 Month</option>
                <option value="1_year" {{ in_array(($filters['preset'] ?? ''), ['1_year', 'last_365_days']) ? 'selected' : '' }}>1 Year</option>
            </select>

            @if(($filters['preset'] ?? 'all_time') !== 'all_time')
                <a href="{{ route('seller.dashboard') }}" class="text-[10px] font-bold uppercase tracking-widest text-gray-400 hover:text-red-600 transition-colors">
                    Reset Filter ✕
                </a>
            @endif
        </div>

        <div class="flex items-center gap-2">
            <span class="text-[10px] font-black uppercase tracking-widest px-3 py-1.5 bg-amber-50 text-amber-700 rounded-full border border-amber-200 flex items-center gap-1.5">
                <svg class="w-3.5 h-3.5 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                Range: {{ $filters['label'] ?? 'All Time' }}
            </span>
        </div>
    </form>

    <div id="seller-dashboard-content" class="space-y-8">
        {{-- Primary KPIs --}}
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
            <div class="bg-white p-6 rounded-2xl border border-gray-100 shadow-sm">
                <div class="flex items-center justify-between mb-4">
                    <div class="text-[10px] font-bold uppercase tracking-widest text-gray-400">Total Revenue</div>
                    <div class="w-8 h-8 rounded-xl bg-[#C0422A]/10 text-[#C0422A] flex items-center justify-center">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                    </div>
                </div>
                <div class="text-2xl font-black text-black">₱{{ number_format($summary['revenue']) }}</div>
                <div class="mt-2 text-[10px] font-bold text-gray-500 uppercase tracking-widest">
                    This month: ₱{{ number_format($summary['thisMonthRevenue']) }} — <span class="{{ $summary['revenueChange'] >= 0 ? 'text-green-600' : 'text-red-600' }}">{{ $summary['revenueChange'] >= 0 ? '+' : '' }}{{ $summary['revenueChange'] }}%</span>
                </div>
            </div>

            <div class="bg-white p-6 rounded-2xl border border-gray-100 shadow-sm">
                <div class="flex items-center justify-between mb-4">
                    <div class="text-[10px] font-bold uppercase tracking-widest text-gray-400">Total Orders</div>
                    <svg class="w-5 h-5 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"></path></svg>
                </div>
                <div class="text-2xl font-black text-black">{{ number_format($summary['orders']) }}</div>
                <div class="mt-2 text-[10px] font-bold text-amber-600 uppercase tracking-widest flex items-center gap-1">
                    <span>{{ $summary['pendingOrders'] }} Need Action</span>
                    <a href="{{ route('seller.orders') }}" class="hover:underline">→</a>
                </div>
            </div>

            <div class="bg-white p-6 rounded-2xl border border-gray-100 shadow-sm">
                <div class="flex items-center justify-between mb-4">
                    <div class="text-[10px] font-bold uppercase tracking-widest text-gray-400">Total Customers</div>
                    <svg class="w-5 h-5 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path></svg>
                </div>
                <div class="text-2xl font-black text-black">{{ number_format($summary['customers']) }}</div>
                <div class="mt-2 text-[10px] font-bold text-gray-400 uppercase tracking-widest">Unique Buyers</div>
            </div>

            <div class="bg-white p-6 rounded-2xl border border-gray-100 shadow-sm">
                <div class="flex items-center justify-between mb-4">
                    <div class="text-[10px] font-bold uppercase tracking-widest text-gray-400">Conv. Rate</div>
                    <svg class="w-5 h-5 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"></path></svg>
                </div>
                <div class="text-2xl font-black text-black">{{ $summary['conversionRate'] }}</div>
                <div class="mt-2 text-[10px] font-bold text-gray-400 uppercase tracking-widest">
                    {{ number_format($summary['productViews']) }} Views (30d)
                </div>
            </div>
        </div>

        {{-- Secondary KPIs --}}
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
            <div class="bg-white p-5 rounded-2xl border border-gray-100 shadow-sm">
                <div class="text-[10px] font-bold uppercase tracking-widest text-gray-400 mb-1">Avg. Order Value</div>
                <div class="text-lg font-black text-black">₱{{ number_format($summary['averageOrderValue']) }}</div>
            </div>
            <div class="bg-white p-5 rounded-2xl border border-gray-100 shadow-sm">
                <div class="text-[10px] font-bold uppercase tracking-widest text-gray-400 mb-1">Product Views</div>
                <div class="text-lg font-black text-black">{{ number_format($summary['productViews']) }}</div>
            </div>
            <div class="bg-white p-5 rounded-2xl border border-gray-100 shadow-sm">
                <div class="text-[10px] font-bold uppercase tracking-widest text-gray-400 mb-1">Add To Cart</div>
                <div class="text-lg font-black text-black">{{ number_format($summary['addToCartEvents']) }}</div>
            </div>
            <div class="bg-white p-5 rounded-2xl border border-gray-100 shadow-sm">
                <div class="text-[10px] font-bold uppercase tracking-widest text-gray-400 mb-1">Live Listings</div>
                <div class="text-lg font-black text-black">{{ number_format($summary['approvedProducts']) }}</div>
            </div>
        </div>

        {{-- Revenue chart + Order pipeline --}}
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">
            <div class="lg:col-span-8 bg-white p-6 rounded-3xl border border-gray-100 shadow-sm">
                <div class="flex items-center justify-between mb-6">
                    <div>
                        <h3 class="text-sm font-bold text-black">Revenue Trend</h3>
                        <p class="text-[10px] text-[#C0422A] font-bold uppercase tracking-widest">Period: {{ $filters['label'] ?? 'All Time' }}</p>
                    </div>
                </div>
                <div class="flex items-end justify-between gap-3 h-40">
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

            <div class="lg:col-span-4 bg-white p-6 rounded-3xl border border-gray-100 shadow-sm">
                <h3 class="text-sm font-bold text-black mb-1">Order Pipeline</h3>
                <p class="text-[10px] text-gray-400 uppercase tracking-widest mb-6">Current status breakdown</p>
                @php
                    $pipeline = [
                        ['key' => 'pending', 'label' => 'Pending', 'color' => 'bg-yellow-500'],
                        ['key' => 'processing', 'label' => 'Processing', 'color' => 'bg-blue-500'],
                        ['key' => 'shipped', 'label' => 'Shipped', 'color' => 'bg-purple-500'],
                        ['key' => 'completed', 'label' => 'Completed', 'color' => 'bg-green-500'],
                    ];
                    $pipelineTotal = max(1, array_sum($statusDistribution));
                @endphp
                <div class="space-y-4">
                    @foreach($pipeline as $stage)
                        <div>
                            <div class="flex justify-between text-[10px] font-bold uppercase tracking-widest text-gray-400 mb-1.5">
                                <span>{{ $stage['label'] }}</span>
                                <span class="text-black">{{ $statusDistribution[$stage['key']] }}</span>
                            </div>
                            @php $stagePct = ($statusDistribution[$stage['key']] / $pipelineTotal) * 100; @endphp
                            <div class="h-2 w-full bg-gray-50 rounded-full overflow-hidden">
                                <div class="{{ $stage['color'] }} h-full rounded-full js-bar-width" data-bar-width="{{ $stagePct }}"></div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>

        {{-- Inventory, Top Products, Prescriptions --}}
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">
            <div class="lg:col-span-4 bg-white p-6 rounded-3xl border border-gray-100 shadow-sm">
                <h3 class="text-sm font-bold text-black mb-6">Inventory Health</h3>
                <div class="space-y-6">
                    @foreach([
                        ['label' => 'Healthy Stock', 'count' => $inventoryHealth['healthy'], 'color' => 'bg-green-500', 'text' => 'text-green-600'],
                        ['label' => 'Low Stock', 'count' => $inventoryHealth['lowStock'], 'color' => 'bg-amber-500', 'text' => 'text-amber-600'],
                        ['label' => 'Out of Stock', 'count' => $inventoryHealth['outOfStock'], 'color' => 'bg-red-500', 'text' => 'text-red-600'],
                    ] as $row)
                        <div>
                            <div class="flex justify-between text-[10px] font-bold uppercase tracking-widest text-gray-400 mb-2">
                                <span>{{ $row['label'] }}</span>
                                <span class="{{ $row['text'] }}">{{ $row['count'] }}</span>
                            </div>
                            @php $rowPct = $inventoryHealth['total'] > 0 ? ($row['count'] / $inventoryHealth['total'] * 100) : 0; @endphp
                            <div class="h-2 w-full bg-gray-50 rounded-full overflow-hidden">
                                <div class="{{ $row['color'] }} h-full rounded-full js-bar-width" data-bar-width="{{ $rowPct }}"></div>
                            </div>
                        </div>
                    @endforeach
                </div>

                @if($lowStockProducts->isNotEmpty())
                    <div class="mt-6 pt-6 border-t border-gray-100">
                        <div class="text-[9px] font-black uppercase tracking-widest text-gray-400 mb-3">Low Stock Alerts</div>
                        <div class="space-y-2">
                            @foreach($lowStockProducts as $product)
                                <a href="{{ route('seller.products.edit', $product['id']) }}"
                                   class="flex items-center justify-between p-3 rounded-xl bg-amber-50/50 border border-amber-100 hover:border-amber-300 transition-all">
                                    <span class="text-xs font-bold text-gray-800">{{ $product['name'] }}</span>
                                    <span class="text-[10px] font-black text-amber-700 uppercase bg-amber-100 px-2 py-0.5 rounded-full">{{ $product['stock'] }} left</span>
                                </a>
                            @endforeach
                        </div>
                    </div>
                @endif
            </div>

            <div class="lg:col-span-4 bg-white p-6 rounded-3xl border border-gray-100 shadow-sm">
                <h3 class="text-sm font-bold text-black mb-6">Top Selling Products</h3>
                <div class="space-y-4">
                    @forelse($topProducts as $index => $topProduct)
                        <div class="flex items-center justify-between p-3 rounded-2xl bg-gray-50/70 border border-gray-100">
                            <div class="flex items-center gap-3">
                                <div class="w-7 h-7 rounded-xl bg-[#3D2B1F]/5 text-[#3D2B1F] text-xs font-black flex items-center justify-center">
                                    {{ $index + 1 }}
                                </div>
                                <div>
                                    <div class="text-xs font-bold text-black">{{ $topProduct->name }}</div>
                                    <div class="text-[10px] text-gray-400 font-bold uppercase">{{ number_format($topProduct->units_sold) }} sold</div>
                                </div>
                            </div>
                            <div class="text-xs font-black text-[#C0422A]">
                                ₱{{ number_format($topProduct->revenue) }}
                            </div>
                        </div>
                    @empty
                        <div class="text-center py-8 text-gray-400 text-xs font-bold">No sales records yet</div>
                    @endforelse
                </div>
            </div>

            <div class="lg:col-span-4 bg-white p-6 rounded-3xl border border-gray-100 shadow-sm">
                <div class="flex items-center justify-between mb-6">
                    <h3 class="text-sm font-bold text-black">Growth Tips</h3>
                    <span class="text-[9px] font-black uppercase tracking-widest text-[#C0422A] bg-[#C0422A]/10 px-2.5 py-1 rounded-full">Smart Advice</span>
                </div>
                <div class="space-y-4">
                    @forelse($prescriptions as $prescription)
                        <div class="p-4 rounded-2xl border {{ $prescription['priority'] === 'urgent' ? 'bg-amber-50/60 border-amber-200' : 'bg-gray-50 border-gray-100' }}">
                            <div class="flex items-center gap-2 mb-1">
                                <span class="w-2 h-2 rounded-full {{ $prescription['priority'] === 'urgent' ? 'bg-amber-500' : 'bg-blue-500' }}"></span>
                                <div class="text-xs font-bold uppercase text-black">{{ $prescription['title'] }}</div>
                            </div>
                            <p class="text-xs text-gray-600 leading-relaxed">{{ $prescription['message'] }}</p>
                            @if(isset($prescription['action_url']) && isset($prescription['action_text']))
                                <a href="{{ $prescription['action_url'] }}" class="inline-block mt-2 text-[10px] font-bold text-[#C0422A] uppercase tracking-widest hover:underline">
                                    {{ $prescription['action_text'] }} →
                                </a>
                            @endif
                        </div>
                    @empty
                        <div class="text-center py-8 text-gray-400 text-xs font-bold">All shop indicators look healthy!</div>
                    @endforelse
                </div>
            </div>
        </div>

        {{-- Recent Activity --}}
        <div class="bg-white p-6 rounded-3xl border border-gray-100 shadow-sm">
            <div class="flex items-center justify-between mb-6">
                <h3 class="text-sm font-bold text-black">Recent Workshop Activity</h3>
                <a href="{{ route('seller.orders') }}" class="text-[10px] font-bold text-[#C0422A] uppercase tracking-widest hover:underline">View all orders →</a>
            </div>
            <div class="max-h-95 overflow-y-auto pr-1 space-y-4 scroll-smooth
                        [&::-webkit-scrollbar]:w-1.5
                        [&::-webkit-scrollbar-track]:bg-gray-50
                        [&::-webkit-scrollbar-track]:rounded-full
                        [&::-webkit-scrollbar-thumb]:bg-gray-200
                        [&::-webkit-scrollbar-thumb]:rounded-full
                        hover:[&::-webkit-scrollbar-thumb]:bg-gray-300">
                @forelse($recentActivity as $activity)
                    <a href="{{ route('seller.orders') }}"
                       class="flex items-center justify-between p-4 rounded-2xl hover:bg-gray-50 transition-all border border-transparent hover:border-gray-100">
                        <div class="flex items-center gap-4">
                            <div class="w-10 h-10 rounded-2xl bg-amber-50 text-amber-700 flex items-center justify-center font-bold">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"></path></svg>
                            </div>
                            <div>
                                <div class="text-xs font-bold text-black">Order #{{ substr($activity['id'], 0, 8) }}</div>
                                <div class="text-[10px] text-gray-400 font-bold uppercase">
                                    Status: <span class="text-gray-700">{{ ucfirst($activity['status']) }}</span>
                                </div>
                            </div>
                        </div>
                        <div class="text-right">
                            <div class="text-xs font-black text-black">₱{{ number_format($activity['amount']) }}</div>
                            <div class="text-[10px] font-bold text-gray-400">
                                {{ $activity['date'] ? \Carbon\Carbon::parse($activity['date'])->format('M d, Y') : '' }}
                            </div>
                        </div>
                    </a>
                @empty
                    <div class="text-center py-8 text-gray-400 text-xs font-bold">No recent sales activity</div>
                @endforelse
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    // Apply dynamic bar heights/widths from data-* attributes (avoids CSS linter false positives)
    document.querySelectorAll('.js-bar-height[data-bar-height]').forEach(function(el) {
        el.style.height = el.dataset.barHeight + '%';
    });
    document.querySelectorAll('.js-bar-width[data-bar-width]').forEach(function(el) {
        el.style.width = el.dataset.barWidth + '%';
    });
    // Background polling every 15 seconds for realtime dashboard updates
    setInterval(function () {
        fetch(window.location.href, {
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        })
        .then(res => res.text())
        .then(html => {
            const parser = new DOMParser();
            const doc = parser.parseFromString(html, 'text/html');
            const newContent = doc.querySelector('#seller-dashboard-content');
            const currentContent = document.querySelector('#seller-dashboard-content');
            if (newContent && currentContent) {
                currentContent.innerHTML = newContent.innerHTML;
            }
        })
        .catch(err => console.debug('Realtime sync skipped:', err));
    }, 15000);
});
</script>
@endsection
