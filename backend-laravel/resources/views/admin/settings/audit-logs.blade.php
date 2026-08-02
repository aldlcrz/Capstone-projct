@extends('layouts.admin')
@section('title', 'Audit Logs')

@section('content')
@php
    $activeTab = (string) ($tab ?? 'all');
@endphp
<div class="space-y-6">

    {{-- Header --}}
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-(--charcoal) font-serif">Audit Logs</h1>
            <p class="text-sm text-(--muted) mt-1">Recent platform activity broken down by type.</p>
        </div>
        <form method="GET" action="{{ route('admin.audit-logs') }}" class="flex gap-2">
            <input type="hidden" name="tab" value="{{ $activeTab }}">
            <input type="text" name="search" value="{{ $search }}" placeholder="Search logs..."
                class="px-4 py-2.5 rounded-xl border border-(--border) text-sm focus:outline-none focus:ring-2 focus:ring-(--rust)/20 focus:border-(--rust) bg-(--cream) w-56">
            <button type="submit" class="px-4 py-2.5 bg-(--rust) text-white text-sm font-bold rounded-xl hover:opacity-90 transition-all">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                </svg>
            </button>
            @if($search)
                <a href="{{ route('admin.audit-logs', ['tab' => $activeTab]) }}"
                   class="px-4 py-2.5 bg-gray-100 text-gray-600 text-sm font-bold rounded-xl hover:bg-gray-200 transition-all">
                    Clear
                </a>
            @endif
        </form>
    </div>

    {{-- Tabs --}}
    @php
        $tabs = [
            'all'      => ['label' => 'All Activity',  'count' => $counts['all'],      'color' => 'text-gray-700',   'dot' => 'bg-gray-400'],
            'orders'   => ['label' => 'Orders',         'count' => $counts['orders'],   'color' => 'text-blue-700',   'dot' => 'bg-blue-500'],
            'products' => ['label' => 'Products',       'count' => $counts['products'], 'color' => 'text-purple-700', 'dot' => 'bg-purple-500'],
            'users'    => ['label' => 'Users',          'count' => $counts['users'],    'color' => 'text-green-700',  'dot' => 'bg-green-500'],
        ];
    @endphp

    <div class="flex gap-2 flex-wrap">
        @foreach($tabs as $key => $t)
            <a href="{{ route('admin.audit-logs', array_filter(['tab' => $key, 'search' => $search])) }}"
               class="flex items-center gap-2 px-4 py-2.5 rounded-xl text-sm font-semibold border transition-all duration-200
                      {{ $activeTab === $key
                          ? 'bg-(--rust) text-white border-(--rust) shadow-sm'
                          : 'bg-white border-(--border) text-(--charcoal) hover:border-(--rust) hover:text-(--rust)' }}">
                <span class="w-2 h-2 rounded-full {{ $activeTab === $key ? 'bg-white/70' : $t['dot'] }}"></span>
                {{ $t['label'] }}
                <span class="ml-1 px-1.5 py-0.5 rounded-md text-[10px] font-bold
                             {{ $activeTab === $key ? 'bg-white/20 text-white' : 'bg-gray-100 text-gray-500' }}">
                    {{ $t['count'] }}
                </span>
            </a>
        @endforeach
    </div>

    {{-- Summary Cards (shown only on the active tab, not "all") --}}
    @if($activeTab !== 'all')
    @php
        $cardConfig = [
            'orders'   => ['bg' => 'bg-blue-50',   'border' => 'border-blue-100',   'text' => 'text-blue-700',   'label' => 'Order Logs',   'icon' => 'M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z'],
            'products' => ['bg' => 'bg-purple-50', 'border' => 'border-purple-100', 'text' => 'text-purple-700', 'label' => 'Product Logs', 'icon' => 'M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z'],
            'users'    => ['bg' => 'bg-green-50',  'border' => 'border-green-100',  'text' => 'text-green-700',  'label' => 'User Logs',    'icon' => 'M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z'],
        ][$activeTab] ?? [];
    @endphp
    @if($cardConfig)
    <div class="flex items-center gap-4 p-4 {{ $cardConfig['bg'] }} border {{ $cardConfig['border'] }} rounded-2xl">
        <div class="w-10 h-10 rounded-xl {{ $cardConfig['bg'] }} border {{ $cardConfig['border'] }} flex items-center justify-center">
            <svg class="w-5 h-5 {{ $cardConfig['text'] }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $cardConfig['icon'] }}"/>
            </svg>
        </div>
        <div>
            <p class="text-sm font-bold {{ $cardConfig['text'] }}">{{ $cardConfig['label'] }}</p>
            <p class="text-xs {{ $cardConfig['text'] }} opacity-70 mt-0.5">
                {{ $counts[$activeTab] }} {{ Str::plural('entry', $counts[$activeTab]) }}{{ $search ? ' matching "' . e($search) . '"' : '' }}
            </p>
        </div>
    </div>
    @endif
    @endif

    {{-- Log Table --}}
    <div class="bg-white rounded-2xl border border-(--border) overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead>
                    <tr class="border-b border-(--border) bg-(--cream)">
                        <th class="text-left px-5 py-3.5 text-[10px] font-bold uppercase tracking-widest text-(--muted)">Time</th>
                        @if($activeTab === 'all')
                        <th class="text-left px-5 py-3.5 text-[10px] font-bold uppercase tracking-widest text-(--muted)">Type</th>
                        @endif
                        <th class="text-left px-5 py-3.5 text-[10px] font-bold uppercase tracking-widest text-(--muted)">
                            {{ $activeTab === 'orders' ? 'Customer' : ($activeTab === 'products' ? 'Seller' : ($activeTab === 'users' ? 'User' : 'Actor')) }}
                        </th>
                        <th class="text-left px-5 py-3.5 text-[10px] font-bold uppercase tracking-widest text-(--muted)">Action</th>
                        <th class="text-left px-5 py-3.5 text-[10px] font-bold uppercase tracking-widest text-(--muted)">Detail</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-(--border)">
                    @forelse($logs as $log)
                    @php
                        $typeStyles = [
                            'order'   => 'bg-blue-50 text-blue-700',
                            'product' => 'bg-purple-50 text-purple-700',
                            'user'    => 'bg-green-50 text-green-700',
                        ];
                        $typeLabels = [
                            'order'   => 'Order',
                            'product' => 'Product',
                            'user'    => 'User',
                        ];
                        $badge = $typeStyles[$log['type']] ?? 'bg-gray-100 text-gray-600';
                        $label = $typeLabels[$log['type']] ?? 'System';

                        $rowAccent = match($log['type']) {
                            'order'   => 'hover:bg-blue-50/40',
                            'product' => 'hover:bg-purple-50/40',
                            'user'    => 'hover:bg-green-50/40',
                            default   => 'hover:bg-(--cream)',
                        };
                    @endphp
                    <tr class="{{ $rowAccent }} transition-colors">
                        <td class="px-5 py-3.5 text-xs text-(--muted) whitespace-nowrap">
                            {{ ($log['time'] instanceof \Carbon\Carbon ? $log['time'] : \Carbon\Carbon::parse($log['time']))->format('M d, Y H:i') }}
                        </td>
                        @if($activeTab === 'all')
                        <td class="px-5 py-3.5">
                            <span class="px-2 py-0.5 rounded-full text-[10px] font-bold uppercase tracking-wider {{ $badge }}">{{ $label }}</span>
                        </td>
                        @endif
                        <td class="px-5 py-3.5 text-sm font-medium text-(--charcoal)">{{ $log['actor'] }}</td>
                        <td class="px-5 py-3.5 text-sm font-bold text-(--charcoal)">{{ $log['action'] }}</td>
                        <td class="px-5 py-3.5 text-xs text-(--muted) max-w-xs truncate">{{ $log['detail'] }}</td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="{{ $activeTab === 'all' ? 5 : 4 }}" class="px-5 py-16 text-center">
                            <svg class="w-10 h-10 text-gray-200 mx-auto mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                            </svg>
                            <p class="text-sm text-(--muted)">No logs found{{ $search ? ' for "' . e($search) . '"' : '' }}.</p>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- Pagination --}}
        @if($logs->hasPages())
        <div class="px-5 py-4 border-t border-(--border) flex items-center justify-between">
            <p class="text-xs text-(--muted)">
                Showing {{ $logs->firstItem() }}–{{ $logs->lastItem() }} of {{ $logs->total() }} entries
            </p>
            <div class="flex gap-1">
                @if($logs->onFirstPage())
                    <span class="px-3 py-1.5 text-xs rounded-lg text-(--muted) bg-(--cream) cursor-not-allowed">← Prev</span>
                @else
                    <a href="{{ $logs->previousPageUrl() }}" class="px-3 py-1.5 text-xs rounded-lg text-(--charcoal) bg-(--cream) hover:bg-(--border) transition-all">← Prev</a>
                @endif
                @if($logs->hasMorePages())
                    <a href="{{ $logs->nextPageUrl() }}" class="px-3 py-1.5 text-xs rounded-lg text-(--charcoal) bg-(--cream) hover:bg-(--border) transition-all">Next →</a>
                @else
                    <span class="px-3 py-1.5 text-xs rounded-lg text-(--muted) bg-(--cream) cursor-not-allowed">Next →</span>
                @endif
            </div>
        </div>
        @endif
    </div>

</div>
@endsection
