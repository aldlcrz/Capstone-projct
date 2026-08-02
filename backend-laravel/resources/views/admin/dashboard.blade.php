@extends('layouts.admin')

@section('content')
@php
    $pendingTotal = array_sum($pendingActions);
@endphp

<div class="space-y-8">

    {{-- ── Header ── --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <div class="text-[10px] font-bold text-[#C0422A] uppercase tracking-[0.2em] mb-1">Enterprise Analytics</div>
            <h1 class="font-serif text-3xl font-bold text-black">Dashboard <span class="text-gray-300 font-light italic">Insights</span></h1>
            <p class="text-xs text-gray-400 mt-1">{{ now()->format('l, F j, Y') }} · Platform overview</p>
        </div>
        <div class="flex items-center gap-2">
            <a href="/admin/export-global-report"
                class="flex items-center gap-2 px-5 py-2.5 bg-[#3D2B1F] text-white rounded-xl text-[10px] font-bold uppercase tracking-widest hover:bg-[#C0422A] transition-all">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                Export CSV
            </a>
        </div>
    </div>

    {{-- ── Pending Action Alerts ── --}}
    @if($pendingTotal > 0)
    <div class="bg-amber-50 border border-amber-200 rounded-2xl p-4 flex flex-wrap items-center gap-3">
        <div class="flex items-center gap-2 text-amber-700">
            <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
            <span class="text-[10px] font-black uppercase tracking-widest">{{ $pendingTotal }} Action{{ $pendingTotal !== 1 ? 's' : '' }} Required</span>
        </div>
        <div class="flex flex-wrap gap-2">
            @if($pendingActions['products'])    <a href="/admin/products?status=pending"      class="px-3 py-1 bg-white border border-amber-200 rounded-full text-[9px] font-black text-amber-700 hover:bg-amber-100 transition-all">{{ $pendingActions['products'] }} Product{{ $pendingActions['products'] !== 1 ? 's' : '' }}</a>@endif
            @if($pendingActions['sellers'])     <a href="/admin/sellers"                      class="px-3 py-1 bg-white border border-amber-200 rounded-full text-[9px] font-black text-amber-700 hover:bg-amber-100 transition-all">{{ $pendingActions['sellers'] }} Seller{{ $pendingActions['sellers'] !== 1 ? 's' : '' }}</a>@endif
            @if($pendingActions['subscriptions'])<a href="/admin/subscriptions"              class="px-3 py-1 bg-white border border-amber-200 rounded-full text-[9px] font-black text-amber-700 hover:bg-amber-100 transition-all">{{ $pendingActions['subscriptions'] }} Subscription{{ $pendingActions['subscriptions'] !== 1 ? 's' : '' }}</a>@endif
            @if($pendingActions['banners'])     <a href="/admin/banners"                      class="px-3 py-1 bg-white border border-amber-200 rounded-full text-[9px] font-black text-amber-700 hover:bg-amber-100 transition-all">{{ $pendingActions['banners'] }} Banner{{ $pendingActions['banners'] !== 1 ? 's' : '' }}</a>@endif
            @if($pendingActions['reports'])     <a href="/admin/reports"                      class="px-3 py-1 bg-white border border-amber-200 rounded-full text-[9px] font-black text-amber-700 hover:bg-amber-100 transition-all">{{ $pendingActions['reports'] }} Report{{ $pendingActions['reports'] !== 1 ? 's' : '' }}</a>@endif
        </div>
    </div>
    @endif

    {{-- ── KPI Cards ── --}}
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
        @php
        $kpis = [
            ['label' => 'Total Revenue',    'value' => $stats['totalSales'],    'sub' => 'All-time gross sales',         'color' => 'text-[#C0422A]', 'bg' => 'bg-red-50',    'icon' => 'M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z'],
            ['label' => 'Net Profit',       'value' => $stats['totalProfit'],   'sub' => 'Revenue minus capital',        'color' => 'text-green-600', 'bg' => 'bg-green-50',  'icon' => 'M13 7h8m0 0v8m0-8l-8 8-4-4-6 6'],
            ['label' => 'Total Orders',     'value' => $stats['totalOrders'],   'sub' => 'Incl. all statuses',           'color' => 'text-blue-600',  'bg' => 'bg-blue-50',   'icon' => 'M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z'],
            ['label' => 'Avg Order Value',  'value' => '₱'.number_format($aov), 'sub' => 'Excl. cancelled orders',       'color' => 'text-purple-600','bg' => 'bg-purple-50', 'icon' => 'M9 7h6m0 10H9m3-3v3m-4 1h8a1 1 0 001-1V6a1 1 0 00-1-1H6a1 1 0 00-1 1v12a1 1 0 001 1z'],
            ['label' => 'Active Customers', 'value' => $userCounts['customers'],'sub' => 'Registered buyers',            'color' => 'text-cyan-600',  'bg' => 'bg-cyan-50',   'icon' => 'M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z'],
            ['label' => 'Verified Sellers', 'value' => $userCounts['sellers'],  'sub' => 'Active artisan shops',         'color' => 'text-amber-600', 'bg' => 'bg-amber-50',  'icon' => 'M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z'],
            ['label' => 'Live Products',    'value' => $stats['liveProducts'],  'sub' => 'All listed products',          'color' => 'text-teal-600',  'bg' => 'bg-teal-50',   'icon' => 'M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 11m8 4V4'],
            ['label' => 'Platform Capital', 'value' => $stats['totalCapital'],  'sub' => 'Cost of goods sold',           'color' => 'text-gray-600',  'bg' => 'bg-gray-50',   'icon' => 'M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4'],
        ];
        @endphp
        @foreach($kpis as $kpi)
        <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-5 flex flex-col gap-3 hover:shadow-md transition-all">
            <div class="flex items-center justify-between">
                <div class="w-9 h-9 rounded-xl {{ $kpi['bg'] }} {{ $kpi['color'] }} flex items-center justify-center shrink-0">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $kpi['icon'] }}"/></svg>
                </div>
            </div>
            <div>
                <div class="text-xl font-black text-black leading-none">{{ $kpi['value'] }}</div>
                <div class="text-[9px] font-black uppercase tracking-widest text-gray-400 mt-1">{{ $kpi['label'] }}</div>
                <div class="text-[9px] text-gray-300 mt-0.5">{{ $kpi['sub'] }}</div>
            </div>
        </div>
        @endforeach
    </div>

    {{-- ── Charts Row ── --}}
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

        {{-- Revenue Sparkline (7 days) --}}
        <div class="lg:col-span-2 bg-white rounded-3xl border border-gray-100 shadow-sm p-6">
            <div class="flex items-center justify-between mb-6">
                <div>
                    <div class="text-[9px] font-black uppercase tracking-widest text-gray-400 mb-0.5">Last 7 Days</div>
                    <h3 class="text-base font-bold text-black">Daily Revenue</h3>
                </div>
                <div class="text-[10px] font-black text-[#C0422A] bg-red-50 px-3 py-1.5 rounded-full">
                    ₱{{ number_format($revenueTrend->sum('revenue'), 0) }} total
                </div>
            </div>
            <canvas id="revenueChart" height="90"></canvas>
        </div>

        {{-- Order Status Donut --}}
        <div class="bg-white rounded-3xl border border-gray-100 shadow-sm p-6 flex flex-col">
            <div class="mb-6">
                <div class="text-[9px] font-black uppercase tracking-widest text-gray-400 mb-0.5">Breakdown</div>
                <h3 class="text-base font-bold text-black">Order Status</h3>
            </div>
            <div class="flex-1 flex items-center justify-center">
                <canvas id="statusChart" width="180" height="180"></canvas>
            </div>
            <div class="mt-4 space-y-1.5">
                @php
                $statusColors = ['Completed'=>'#22c55e','Pending'=>'#f59e0b','Processing'=>'#3b82f6','Shipped'=>'#8b5cf6','Cancelled'=>'#ef4444','Delivered'=>'#10b981'];
                @endphp
                @foreach($orderStatuses as $status => $count)
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-2">
                        <div class="w-2 h-2 rounded-full shrink-0" style="background:{{ $statusColors[$status] ?? '#9ca3af' }}"></div>
                        <span class="text-[9px] font-bold text-gray-600 uppercase tracking-wider">{{ $status }}</span>
                    </div>
                    <span class="text-[9px] font-black text-black">{{ $count }}</span>
                </div>
                @endforeach
            </div>
        </div>
    </div>

    {{-- ── User Registrations Chart ── --}}
    <div class="bg-white rounded-3xl border border-gray-100 shadow-sm p-6">
        <div class="flex items-center justify-between mb-6">
            <div>
                <div class="text-[9px] font-black uppercase tracking-widest text-gray-400 mb-0.5">Last 7 Days</div>
                <h3 class="text-base font-bold text-black">User Registrations</h3>
            </div>
            <div class="flex items-center gap-4 text-[9px] font-bold uppercase tracking-widest">
                <span class="text-gray-400">Customers: <span class="text-black">{{ $userCounts['customers'] }}</span></span>
                <span class="text-gray-400">Sellers: <span class="text-black">{{ $userCounts['sellers'] }}</span></span>
            </div>
        </div>
        <canvas id="userChart" height="55"></canvas>
    </div>

    {{-- ── Tables Row ── --}}
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">

        {{-- Top Sellers --}}
        <div class="bg-white rounded-3xl border border-gray-100 shadow-sm p-6">
            <div class="flex items-center justify-between mb-5">
                <h3 class="text-sm font-bold text-black">Top Sellers</h3>
                <a href="/admin/sellers" class="text-[9px] font-black uppercase tracking-widest text-[#C0422A] hover:underline">View All</a>
            </div>
            @if($topSellers->isEmpty())
                <p class="text-xs text-gray-300 text-center py-6">No sales data yet</p>
            @else
            <div class="space-y-3">
                @foreach($topSellers as $i => $row)
                <div class="flex items-center gap-3">
                    <div class="w-6 h-6 rounded-full bg-gray-100 flex items-center justify-center text-[9px] font-black text-gray-500 shrink-0">{{ $i+1 }}</div>
                    <div class="flex-1 min-w-0">
                        <div class="text-xs font-bold text-black truncate">{{ $row->seller?->shopName ?: $row->seller?->name ?: 'Unknown' }}</div>
                        <div class="text-[9px] text-gray-400">{{ $row->orders }} orders</div>
                    </div>
                    <div class="text-xs font-black text-[#C0422A] shrink-0">₱{{ number_format($row->revenue) }}</div>
                </div>
                @php $maxRev = $topSellers->first()->revenue ?: 1; @endphp
                <div class="h-1 bg-gray-100 rounded-full overflow-hidden -mt-1">
                    <div class="h-full bg-[#C0422A]/30 rounded-full" style="width:{{ round(($row->revenue / $maxRev) * 100) }}%"></div>
                </div>
                @endforeach
            </div>
            @endif
        </div>

        {{-- Top Products --}}
        <div class="bg-white rounded-3xl border border-gray-100 shadow-sm p-6">
            <div class="flex items-center justify-between mb-5">
                <h3 class="text-sm font-bold text-black">Top Products</h3>
                <a href="/admin/products" class="text-[9px] font-black uppercase tracking-widest text-[#C0422A] hover:underline">View All</a>
            </div>
            @if($topProducts->isEmpty())
                <p class="text-xs text-gray-300 text-center py-6">No sales data yet</p>
            @else
            <div class="space-y-3">
                @foreach($topProducts as $i => $row)
                <div class="flex items-center gap-3">
                    <div class="w-6 h-6 rounded-full bg-gray-100 flex items-center justify-center text-[9px] font-black text-gray-500 shrink-0">{{ $i+1 }}</div>
                    <div class="flex-1 min-w-0">
                        <div class="text-xs font-bold text-black truncate">{{ $row->product?->name ?: 'Unknown Product' }}</div>
                        <div class="text-[9px] text-gray-400">₱{{ number_format($row->revenue) }} revenue</div>
                    </div>
                    <div class="text-xs font-black text-blue-600 shrink-0">{{ $row->units }} units</div>
                </div>
                @php $maxUnits = $topProducts->first()->units ?: 1; @endphp
                <div class="h-1 bg-gray-100 rounded-full overflow-hidden -mt-1">
                    <div class="h-full bg-blue-400/30 rounded-full" style="width:{{ round(($row->units / $maxUnits) * 100) }}%"></div>
                </div>
                @endforeach
            </div>
            @endif
        </div>
    </div>

    {{-- ── Activity Feed + Quick Links ── --}}
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

        {{-- Recent Activity --}}
        <div class="lg:col-span-2 bg-white rounded-3xl border border-gray-100 shadow-sm p-6">
            <div class="flex items-center justify-between mb-5">
                <h3 class="text-sm font-bold text-black">Recent Activity</h3>
                <a href="{{ route('admin.notifications.index') }}" class="text-[9px] font-black uppercase tracking-widest text-[#C0422A] hover:underline">View All</a>
            </div>
            <div class="space-y-1">
                @forelse($recentActivity as $activity)
                <div class="flex items-start gap-3 py-2.5 border-b border-gray-50 last:border-0">
                    <div class="w-7 h-7 rounded-full bg-gray-50 border border-gray-100 flex items-center justify-center text-gray-400 shrink-0 mt-0.5">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    </div>
                    <div class="flex-1 min-w-0">
                        <div class="text-xs font-bold text-black truncate">{{ $activity->title }}</div>
                        <div class="text-[9px] text-gray-400">{{ $activity->createdAt->diffForHumans() }}</div>
                    </div>
                    <div class="text-[8px] font-black text-gray-300 uppercase tracking-widest shrink-0 mt-0.5">{{ $activity->type ?? 'system' }}</div>
                </div>
                @empty
                <p class="text-xs text-gray-300 text-center py-6">No recent activity</p>
                @endforelse
            </div>
        </div>

        {{-- Quick Action Panel --}}
        <div class="space-y-3">
            <div class="bg-[#3D2B1F] text-white rounded-3xl p-6">
                <div class="text-[9px] font-black uppercase tracking-widest text-white/40 mb-1">Net Profit</div>
                <div class="text-3xl font-black text-[#C0422A] mt-2">{{ $stats['totalProfit'] }}</div>
                <div class="text-[9px] text-white/50 mt-1">Revenue: {{ $stats['totalRevenue'] }}</div>
                <div class="text-[9px] text-white/50">Capital: {{ $stats['totalCapital'] }}</div>
                <div class="mt-4 pt-4 border-t border-white/10 flex items-center gap-1.5">
                    <span class="text-[9px] text-green-400 font-black">↑ Healthy</span>
                    <span class="text-[9px] text-white/30">vs platform cost</span>
                </div>
            </div>

            <div class="bg-white rounded-3xl border border-gray-100 shadow-sm p-5">
                <div class="text-[9px] font-black uppercase tracking-widest text-gray-400 mb-3">Quick Links</div>
                <div class="space-y-1.5">
                    @foreach([
                        ['label'=>'Pending Products', 'href'=>'/admin/products?status=pending', 'count'=>$pendingActions['products']],
                        ['label'=>'Pending Sellers',  'href'=>'/admin/sellers',                  'count'=>$pendingActions['sellers']],
                        ['label'=>'Subscriptions',    'href'=>'/admin/subscriptions',            'count'=>$pendingActions['subscriptions']],
                        ['label'=>'Banner Requests',  'href'=>'/admin/banners',                  'count'=>$pendingActions['banners']],
                        ['label'=>'Open Reports',     'href'=>'/admin/reports',                  'count'=>$pendingActions['reports']],
                    ] as $link)
                    <a href="{{ $link['href'] }}" class="flex items-center justify-between px-3 py-2 rounded-xl hover:bg-gray-50 transition-all group">
                        <span class="text-[10px] font-bold text-gray-700 group-hover:text-[#C0422A] transition-colors">{{ $link['label'] }}</span>
                        @if($link['count'] > 0)
                            <span class="px-2 py-0.5 bg-red-500 text-white text-[8px] font-black rounded-full">{{ $link['count'] }}</span>
                        @else
                            <span class="text-[8px] font-bold text-gray-300">—</span>
                        @endif
                    </a>
                    @endforeach
                </div>
            </div>
        </div>
    </div>

</div>

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script>
const rust  = '#C0422A';
const muted = '#e5e7eb';

// ── Revenue Chart ───────────────────────────────────────────────────────────
new Chart(document.getElementById('revenueChart'), {
    type: 'bar',
    data: {
        labels: {!! json_encode($revenueTrend->pluck('date')) !!},
        datasets: [{
            label: 'Revenue (₱)',
            data: {!! json_encode($revenueTrend->pluck('revenue')) !!},
            backgroundColor: 'rgba(192,66,42,0.12)',
            borderColor: rust,
            borderWidth: 2,
            borderRadius: 8,
            borderSkipped: false,
        }]
    },
    options: {
        responsive: true, maintainAspectRatio: true,
        plugins: { legend: { display: false }, tooltip: { callbacks: { label: ctx => ' ₱' + ctx.parsed.y.toLocaleString() } } },
        scales: {
            x: { grid: { display: false }, ticks: { font: { size: 10, weight: '700' }, color: '#9ca3af' } },
            y: { grid: { color: '#f3f4f6' }, ticks: { font: { size: 10 }, color: '#9ca3af', callback: v => '₱' + v.toLocaleString() }, beginAtZero: true }
        }
    }
});

// ── Order Status Donut ──────────────────────────────────────────────────────
@php
$statusData   = $orderStatuses->values()->toArray();
$statusLabels = $orderStatuses->keys()->toArray();
$colorMap = ['Completed'=>'#22c55e','Pending'=>'#f59e0b','Processing'=>'#3b82f6','Shipped'=>'#8b5cf6','Cancelled'=>'#ef4444','Delivered'=>'#10b981'];
$colors = array_map(fn($s) => $colorMap[$s] ?? '#9ca3af', $statusLabels);
@endphp
new Chart(document.getElementById('statusChart'), {
    type: 'doughnut',
    data: {
        labels: {!! json_encode($statusLabels) !!},
        datasets: [{ data: {!! json_encode($statusData) !!}, backgroundColor: {!! json_encode($colors) !!}, borderWidth: 0, hoverOffset: 4 }]
    },
    options: {
        responsive: false,
        plugins: { legend: { display: false }, tooltip: { callbacks: { label: ctx => ' ' + ctx.label + ': ' + ctx.parsed } } },
        cutout: '72%'
    }
});

// ── User Registrations Chart ────────────────────────────────────────────────
new Chart(document.getElementById('userChart'), {
    type: 'line',
    data: {
        labels: {!! json_encode($userTrend->pluck('date')) !!},
        datasets: [{
            label: 'New Users',
            data: {!! json_encode($userTrend->pluck('count')) !!},
            borderColor: '#3b82f6',
            backgroundColor: 'rgba(59,130,246,0.06)',
            borderWidth: 2,
            pointRadius: 4,
            pointBackgroundColor: '#3b82f6',
            fill: true,
            tension: 0.4,
        }]
    },
    options: {
        responsive: true, maintainAspectRatio: true,
        plugins: { legend: { display: false } },
        scales: {
            x: { grid: { display: false }, ticks: { font: { size: 10, weight: '700' }, color: '#9ca3af' } },
            y: { grid: { color: '#f3f4f6' }, ticks: { font: { size: 10 }, color: '#9ca3af', stepSize: 1 }, beginAtZero: true }
        }
    }
});
</script>
@endpush

@endsection
