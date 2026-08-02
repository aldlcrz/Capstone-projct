@extends('layouts.seller')

@section('content')
<div class="space-y-8">
    <div class="flex items-center justify-between">
        <div>
            <div class="text-[10px] font-bold text-[#C0422A] uppercase tracking-[0.2em] mb-1">Seller Performance</div>
            <h1 class="font-serif text-3xl font-bold text-black uppercase">Seller <span class="text-[#C0422A] italic lowercase">dashboard</span></h1>
        </div>
        <div class="flex items-center gap-3">
            <a href="{{ route('seller.export') }}" class="flex items-center gap-2 px-6 py-3 bg-[#3D2B1F] text-white rounded-xl text-[10px] font-bold uppercase tracking-widest hover:bg-[#C0422A] transition-all shadow-lg shadow-black/5">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                Export CSV
            </a>
        </div>
    </div>

    {{-- Primary KPIs --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
        <div class="bg-[#C0422A] p-6 rounded-2xl shadow-xl shadow-[#C0422A]/10 text-white">
            <div class="flex items-center justify-between mb-4">
                <div class="text-[10px] font-bold uppercase tracking-widest text-white/60">Total Revenue</div>
                <svg class="w-5 h-5 text-white/40" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
            </div>
            <div class="text-2xl font-black">₱{{ number_format($summary['revenue']) }}</div>
            <div class="text-[10px] text-white/70 mt-2 font-bold uppercase tracking-widest">
                This month: ₱{{ number_format($summary['thisMonthRevenue']) }}
                @if($summary['revenueChange'] > 0)
                    <span class="text-green-200">↑ {{ $summary['revenueChange'] }}%</span>
                @elseif($summary['revenueChange'] < 0)
                    <span class="text-red-200">↓ {{ abs($summary['revenueChange']) }}%</span>
                @else
                    <span>— flat</span>
                @endif
            </div>
        </div>

        <div class="bg-white p-6 rounded-2xl border border-gray-100 shadow-sm">
            <div class="flex items-center justify-between mb-4">
                <div class="text-[10px] font-bold uppercase tracking-widest text-gray-400">Total Orders</div>
                <svg class="w-5 h-5 text-gray-200" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"></path></svg>
            </div>
            <div class="text-2xl font-black text-black">{{ $summary['orders'] }}</div>
            @if($summary['pendingOrders'] > 0)
                <a href="{{ route('seller.orders') }}" class="inline-block mt-2 text-[10px] font-bold uppercase tracking-widest text-amber-600 hover:underline">
                    {{ $summary['pendingOrders'] }} need action →
                </a>
            @endif
        </div>

        <div class="bg-white p-6 rounded-2xl border border-gray-100 shadow-sm">
            <div class="flex items-center justify-between mb-4">
                <div class="text-[10px] font-bold uppercase tracking-widest text-gray-400">Total Customers</div>
                <svg class="w-5 h-5 text-gray-200" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path></svg>
            </div>
            <div class="text-2xl font-black text-black">{{ $summary['customers'] }}</div>
            <div class="text-[10px] text-gray-400 mt-2 font-bold uppercase tracking-widest">Unique buyers</div>
        </div>

        <div class="bg-white p-6 rounded-2xl border border-gray-100 shadow-sm">
            <div class="flex items-center justify-between mb-4">
                <div class="text-[10px] font-bold uppercase tracking-widest text-gray-400">Conv. Rate</div>
                <svg class="w-5 h-5 text-gray-200" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"></path></svg>
            </div>
            <div class="text-2xl font-black text-black">{{ $summary['conversionRate'] }}</div>
            <div class="text-[10px] text-gray-400 mt-2 font-bold uppercase tracking-widest">{{ number_format($summary['productViews']) }} views (30d)</div>
        </div>
    </div>

    {{-- Secondary KPIs --}}
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
        <div class="bg-white p-4 rounded-2xl border border-gray-100 shadow-sm">
            <div class="text-[9px] font-bold uppercase tracking-widest text-gray-400 mb-1">Avg. Order Value</div>
            <div class="text-lg font-black text-black">₱{{ number_format($summary['averageOrderValue']) }}</div>
        </div>
        <div class="bg-white p-4 rounded-2xl border border-gray-100 shadow-sm">
            <div class="text-[9px] font-bold uppercase tracking-widest text-gray-400 mb-1">Product Views</div>
            <div class="text-lg font-black text-black">{{ number_format($summary['productViews']) }}</div>
        </div>
        <div class="bg-white p-4 rounded-2xl border border-gray-100 shadow-sm">
            <div class="text-[9px] font-bold uppercase tracking-widest text-gray-400 mb-1">Add to Cart</div>
            <div class="text-lg font-black text-black">{{ number_format($summary['addToCartEvents']) }}</div>
        </div>
        <div class="bg-white p-4 rounded-2xl border border-gray-100 shadow-sm">
            <div class="text-[9px] font-bold uppercase tracking-widest text-gray-400 mb-1">Live Listings</div>
            <div class="text-lg font-black text-black">{{ $summary['approvedProducts'] }}</div>
        </div>
    </div>

    {{-- Revenue chart + Order pipeline --}}
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">
        <div class="lg:col-span-8 bg-white p-6 rounded-3xl border border-gray-100 shadow-sm">
            <div class="flex items-center justify-between mb-6">
                <div>
                    <h3 class="text-sm font-bold text-black">Revenue Trend</h3>
                    <p class="text-[10px] text-gray-400 uppercase tracking-widest">Last 7 days</p>
                </div>
            </div>
            <div class="flex items-end justify-between gap-3 h-40">
                @foreach($revenueChart as $day)
                    <div class="flex-1 flex flex-col items-center gap-2">
                        <div class="w-full flex items-end justify-center h-28">
                            <div class="w-full max-w-[40px] bg-[#C0422A]/10 rounded-t-lg relative group"
                                 style="height: {{ $maxChartRevenue > 0 ? max(8, ($day['revenue'] / $maxChartRevenue) * 100) : 8 }}%">
                                <div class="absolute inset-x-0 bottom-0 bg-[#C0422A] rounded-t-lg transition-all"
                                     style="height: 100%"></div>
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
                        <div class="h-2 w-full bg-gray-50 rounded-full overflow-hidden">
                            <div class="{{ $stage['color'] }} h-full rounded-full"
                                 style="width: {{ ($statusDistribution[$stage['key']] / $pipelineTotal) * 100 }}%"></div>
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
                        <div class="h-2 w-full bg-gray-50 rounded-full overflow-hidden">
                            <div class="{{ $row['color'] }} h-full rounded-full"
                                 style="width: {{ $inventoryHealth['total'] > 0 ? ($row['count'] / $inventoryHealth['total'] * 100) : 0 }}%"></div>
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
                                <span class="text-xs font-bold text-black truncate pr-2">{{ $product['name'] }}</span>
                                <span class="text-[10px] font-black text-amber-600 shrink-0">{{ $product['stock'] }} left</span>
                            </a>
                        @endforeach
                    </div>
                </div>
            @endif
        </div>

        <div class="lg:col-span-4 bg-white p-6 rounded-3xl border border-gray-100 shadow-sm">
            <h3 class="text-sm font-bold text-black mb-1">Top Selling Products</h3>
            <p class="text-[10px] text-gray-400 uppercase tracking-widest mb-6">By units sold</p>

            @forelse($topProducts as $index => $product)
                <div class="flex items-center justify-between py-3 {{ !$loop->last ? 'border-b border-gray-50' : '' }}">
                    <div class="flex items-center gap-3 min-w-0">
                        <div class="w-7 h-7 rounded-lg bg-[#F9F7F4] text-[#C0422A] flex items-center justify-center text-[10px] font-black shrink-0">
                            {{ $index + 1 }}
                        </div>
                        <div class="min-w-0">
                            <div class="text-xs font-bold text-black truncate">{{ $product->name }}</div>
                            <div class="text-[9px] text-gray-400 font-bold uppercase tracking-widest">{{ $product->units_sold }} sold</div>
                        </div>
                    </div>
                    <div class="text-xs font-black text-[#C0422A] shrink-0">₱{{ number_format($product->revenue) }}</div>
                </div>
            @empty
                <div class="py-8 text-center text-xs text-gray-400 italic">No sales data yet. Your bestsellers will appear here.</div>
            @endforelse
        </div>

        <div class="lg:col-span-4 bg-white p-6 rounded-3xl border border-gray-100 shadow-sm">
            <div class="flex items-center justify-between mb-6">
                <div>
                    <h3 class="text-sm font-bold text-black">Growth Tips</h3>
                    <p class="text-[10px] text-gray-400 uppercase tracking-widest">Actionable advice</p>
                </div>
                <span class="px-2 py-0.5 bg-red-50 text-[#C0422A] text-[9px] font-black uppercase tracking-widest rounded border border-red-100">Smart Advice</span>
            </div>

            <div class="space-y-3">
                @foreach($prescriptions as $p)
                    <div class="p-4 rounded-2xl bg-[#F9F7F4] border border-[#E5DDD5]">
                        <div class="flex items-center gap-2 mb-2">
                            <div class="w-1.5 h-1.5 rounded-full {{ $p['priority'] === 'urgent' ? 'bg-red-500 animate-pulse' : ($p['priority'] === 'warning' ? 'bg-amber-500' : 'bg-blue-500') }}"></div>
                            <span class="text-[10px] font-bold uppercase tracking-widest text-black">{{ $p['title'] }}</span>
                        </div>
                        <p class="text-xs text-gray-500 mb-3">{{ $p['message'] }}</p>
                        <a href="{{ $p['url'] ?? '#' }}" class="text-[10px] font-bold text-[#C0422A] uppercase tracking-widest hover:underline">{{ $p['action'] }} →</a>
                    </div>
                @endforeach
            </div>
        </div>
    </div>

    {{-- Recent Activity --}}
    <div class="bg-white p-6 rounded-3xl border border-gray-100 shadow-sm">
        <div class="flex items-center justify-between mb-6">
            <h3 class="text-sm font-bold text-black">Recent Workshop Activity</h3>
            <a href="{{ route('seller.orders') }}" class="text-[10px] font-bold text-[#C0422A] uppercase tracking-widest hover:underline">View all orders →</a>
        </div>
        <div class="max-h-[380px] overflow-y-auto pr-1 space-y-4 scroll-smooth
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
                        <div class="w-10 h-10 rounded-full bg-[#F8F7F4] flex items-center justify-center text-[#C0422A]">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"></path></svg>
                        </div>
                        <div>
                            <div class="text-xs font-bold text-black">Order #{{ strtoupper(substr($activity['id'], -8)) }}</div>
                            <div class="text-[10px] text-gray-400">Status: <span class="capitalize text-amber-600 font-bold">{{ $activity['status'] }}</span></div>
                        </div>
                    </div>
                    <div class="text-right">
                        <div class="text-xs font-bold text-black">₱{{ number_format($activity['amount']) }}</div>
                        <div class="text-[9px] text-gray-400">{{ \Carbon\Carbon::parse($activity['date'])->format('M d, Y') }}</div>
                    </div>
                </a>
            @empty
                <div class="py-12 text-center text-xs text-gray-400 italic">Your workshop hasn't had any recent activity. <a href="{{ route('seller.products.create') }}" class="text-[#C0422A] font-bold hover:underline">List your first product</a> to get started.</div>
            @endforelse
        </div>
    </div>
</div>
@endsection
