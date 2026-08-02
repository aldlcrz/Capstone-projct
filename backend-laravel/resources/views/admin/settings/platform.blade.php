@extends('layouts.admin')
@section('title', 'Platform Info')

@section('content')
<div class="space-y-8">
    {{-- Header --}}
    <div>
        <h1 class="text-2xl font-bold text-(--charcoal) font-serif">Platform Info</h1>
        <p class="text-sm text-(--muted) mt-1">Technical details about the server, runtime, and database.</p>
    </div>

    {{-- Stats Strip --}}
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
        @foreach([
            ['label' => 'Total Users',    'value' => $info['stats']['total_users'],    'icon' => 'M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z', 'color' => 'blue'],
            ['label' => 'Total Orders',   'value' => $info['stats']['total_orders'],   'icon' => 'M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z', 'color' => 'green'],
            ['label' => 'Products',       'value' => $info['stats']['total_products'], 'icon' => 'M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z', 'color' => 'purple'],
            ['label' => 'Gross Revenue',  'value' => $info['stats']['total_revenue'],  'icon' => 'M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z', 'color' => 'amber'],
        ] as $stat)
        @php
            $colors = ['blue'=>'bg-blue-50 text-blue-600','green'=>'bg-green-50 text-green-600','purple'=>'bg-purple-50 text-purple-600','amber'=>'bg-amber-50 text-amber-600'];
            $c = $colors[$stat['color']];
        @endphp
        <div class="bg-white rounded-2xl border border-(--border) p-5">
            <div class="w-10 h-10 rounded-xl {{ $c }} flex items-center justify-center mb-3">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $stat['icon'] }}"/></svg>
            </div>
            <p class="text-xl font-black text-(--charcoal)">{{ $stat['value'] }}</p>
            <p class="text-xs text-(--muted) mt-0.5 uppercase tracking-wider font-bold">{{ $stat['label'] }}</p>
        </div>
        @endforeach
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        {{-- Application --}}
        <div class="bg-white rounded-2xl border border-(--border) p-6 space-y-4">
            <h2 class="text-xs font-bold uppercase tracking-widest text-(--muted) pb-3 border-b border-(--border) flex items-center gap-2">
                <svg class="w-4 h-4 text-(--rust)" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 20l4-16m4 4l4 4-4 4M6 16l-4-4 4-4"/></svg>
                Application
            </h2>
            @foreach($info['app'] as $key => $value)
            <div class="flex justify-between items-start gap-3">
                <span class="text-xs text-(--muted) capitalize">{{ str_replace('_', ' ', $key) }}</span>
                <span class="text-xs font-semibold text-(--charcoal) text-right break-all max-w-[60%]">{{ $value }}</span>
            </div>
            @endforeach
        </div>

        {{-- PHP Runtime --}}
        <div class="bg-white rounded-2xl border border-(--border) p-6 space-y-4">
            <h2 class="text-xs font-bold uppercase tracking-widest text-(--muted) pb-3 border-b border-(--border) flex items-center gap-2">
                <svg class="w-4 h-4 text-(--rust)" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 3H5a2 2 0 00-2 2v4m6-6h10a2 2 0 012 2v4M9 3v18m0 0h10a2 2 0 002-2V9M9 21H5a2 2 0 01-2-2V9m0 0h18"/></svg>
                PHP Runtime
            </h2>
            @foreach($info['php'] as $key => $value)
            <div class="flex justify-between items-start gap-3">
                <span class="text-xs text-(--muted) capitalize">{{ str_replace('_', ' ', $key) }}</span>
                <span class="text-xs font-semibold text-(--charcoal) text-right break-all max-w-[55%]">{{ $value }}</span>
            </div>
            @endforeach
        </div>

        {{-- Database --}}
        <div class="bg-white rounded-2xl border border-(--border) p-6 space-y-4">
            <h2 class="text-xs font-bold uppercase tracking-widest text-(--muted) pb-3 border-b border-(--border) flex items-center gap-2">
                <svg class="w-4 h-4 text-(--rust)" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 7v10c0 2.21 3.582 4 8 4s8-1.79 8-4V7M4 7c0 2.21 3.582 4 8 4s8-1.79 8-4M4 7c0-2.21 3.582-4 8-4s8 1.79 8 4m0 5c0 2.21-3.582 4-8 4s-8-1.79-8-4"/></svg>
                Database
            </h2>
            @foreach($info['database'] as $key => $value)
            <div class="flex justify-between items-start gap-3">
                <span class="text-xs text-(--muted) capitalize">{{ str_replace('_', ' ', $key) }}</span>
                <span class="text-xs font-semibold text-(--charcoal) text-right">{{ $value }}</span>
            </div>
            @endforeach
            <div class="flex justify-between items-start gap-3">
                <span class="text-xs text-(--muted)">Database Size</span>
                <span class="text-xs font-semibold text-(--charcoal)">{{ $info['stats']['db_size'] }}</span>
            </div>
        </div>
    </div>
</div>
@endsection
