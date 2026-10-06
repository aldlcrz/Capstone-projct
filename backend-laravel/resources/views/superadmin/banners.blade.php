@extends('layouts.superadmin')

@section('content')
<div class="space-y-6">

    {{-- Page Header --}}
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div class="text-left space-y-0.5">
            <div class="inline-flex items-center gap-2">
                <span class="text-[9px] font-black uppercase tracking-[0.25em] text-[#C0422A]">Content &amp; Marketing</span>
                <span class="text-gray-300 text-xs">·</span>
                <span class="text-[9px] font-bold uppercase tracking-[0.2em] text-gray-400">Hero Promotions</span>
                <span class="text-gray-300 text-xs">·</span>
                <span class="text-[9px] font-black uppercase tracking-wider bg-gray-100 text-gray-600 px-2 py-0.5 rounded-full border border-gray-200">View Only</span>
            </div>
            <h1 class="font-serif text-2xl sm:text-3xl font-bold text-gray-900 leading-tight">
                Homepage <span class="text-[#C0420A] font-light italic">Promotions</span>
            </h1>
            <p class="text-[11px] text-gray-400 font-medium">Browse featured artisan products, shops, and hero promotions in view-only mode</p>
        </div>
    </div>

    {{-- ── ALL PROMOTIONS ── --}}
    <div class="space-y-4">
        @if($banners->isEmpty())
            <div class="bg-white rounded-2xl border border-gray-100 p-16 text-center shadow-xs">
                <div class="w-16 h-16 bg-gray-50 rounded-2xl flex items-center justify-center mx-auto mb-4 border border-gray-100">
                    <svg class="w-8 h-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                    </svg>
                </div>
                <h3 class="text-sm font-bold text-gray-900 uppercase tracking-widest mb-1">No Promotions Created</h3>
                <p class="text-xs text-gray-400 max-w-sm mx-auto">There are currently no active or scheduled homepage hero promotions.</p>
            </div>
        @else
            <div class="bg-white rounded-2xl border border-gray-100 overflow-hidden shadow-sm">
                <div class="p-4 bg-amber-50/60 border-b border-amber-100/80 flex items-center justify-between text-xs text-amber-900">
                    <div class="flex items-center gap-2">
                        <svg class="w-4 h-4 text-[#C0422A]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        <span><strong>Hero Carousel Order:</strong> Order <strong>#1</strong> appears first on the marketplace homepage.</span>
                    </div>
                </div>

                <div class="overflow-x-auto no-scrollbar">
                    <table class="w-full text-left border-collapse min-w-175 text-xs">
                        <thead>
                            <tr class="bg-[#F8F7F4] border-b border-gray-100 text-gray-400 uppercase tracking-widest font-bold text-[9px]">
                                <th class="px-5 py-3.5 text-center w-20">Order</th>
                                <th class="px-5 py-3.5 w-40">Preview</th>
                                <th class="px-5 py-3.5">Promotion Details</th>
                                <th class="px-5 py-3.5 w-48">Schedule</th>
                                <th class="px-5 py-3.5 text-right w-32">Status</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @foreach($banners as $index => $banner)
                                @php
                                    $isLive = $banner->isCurrentlyLive();
                                    $now = now();
                                    $isScheduled = $banner->is_active && $banner->start_date && $banner->start_date > $now;
                                    $isExpired = $banner->is_active && $banner->end_date && $banner->end_date < $now;
                                @endphp
                                <tr class="hover:bg-gray-50/80 transition-colors group">
                                    {{-- Order --}}
                                    <td class="px-5 py-4 text-center">
                                        <span class="inline-flex w-7 h-7 rounded-xl bg-gray-100 text-gray-800 font-mono text-xs font-bold items-center justify-center">
                                            #{{ $banner->order_index }}
                                        </span>
                                    </td>

                                    {{-- Preview Image --}}
                                    <td class="px-5 py-4">
                                        <div class="w-36 aspect-video rounded-xl overflow-hidden bg-gray-900 border border-gray-100 shadow-xs relative">
                                            <img src="{{ $banner->getImageUrl() }}" class="w-full h-full object-cover" alt="Banner">
                                            <div class="absolute inset-0 bg-linear-to-r from-black/60 to-transparent"></div>
                                        </div>
                                    </td>

                                    {{-- Details --}}
                                    <td class="px-5 py-4">
                                        <div class="space-y-1">
                                            @if($banner->subtitle)
                                                <div class="text-[9px] font-bold text-[#C0422A] uppercase tracking-widest">{{ $banner->subtitle }}</div>
                                            @endif
                                            <div class="text-sm font-bold text-gray-900">{{ $banner->title ?: 'Untitled Promotion' }}</div>
                                            
                                            <div class="flex items-center gap-2 flex-wrap pt-0.5">
                                                @if($banner->button_text_1 && $banner->button_url_1)
                                                    <span class="inline-flex items-center gap-1 px-2 py-0.5 bg-gray-100 text-gray-700 rounded text-[9px] font-bold">
                                                        <span class="w-1.5 h-1.5 rounded-full bg-[#C0422A]"></span>
                                                        {{ $banner->button_text_1 }} → <span class="text-gray-500 font-normal font-mono">{{ Str::limit($banner->button_url_1, 28) }}</span>
                                                    </span>
                                                @endif
                                                @if($banner->button_text_2 && $banner->button_url_2)
                                                    <span class="inline-flex items-center gap-1 px-2 py-0.5 bg-gray-100 text-gray-700 rounded text-[9px] font-bold">
                                                        {{ $banner->button_text_2 }} → <span class="text-gray-500 font-normal font-mono">{{ Str::limit($banner->button_url_2, 28) }}</span>
                                                    </span>
                                                @endif
                                            </div>
                                        </div>
                                    </td>

                                    {{-- Schedule info --}}
                                    <td class="px-5 py-4 text-xs text-gray-600">
                                        @if($banner->start_date || $banner->end_date)
                                            <div class="space-y-0.5 text-[10px]">
                                                @if($banner->start_date)
                                                    <div><span class="text-gray-400 font-medium">Start:</span> {{ $banner->start_date->format('M d, Y h:i A') }}</div>
                                                @endif
                                                @if($banner->end_date)
                                                    <div><span class="text-gray-400 font-medium">End:</span> {{ $banner->end_date->format('M d, Y h:i A') }}</div>
                                                @endif
                                            </div>
                                        @else
                                            <span class="text-[10px] text-gray-400 italic">Always active</span>
                                        @endif
                                    </td>

                                    {{-- Status Badge --}}
                                    <td class="px-5 py-4 text-right">
                                        @if(!$banner->is_active)
                                            <span class="inline-flex items-center px-2.5 py-1 rounded-full text-[9px] font-black uppercase tracking-wider bg-gray-100 text-gray-600 border border-gray-200">
                                                Hidden
                                            </span>
                                        @elseif($isLive)
                                            <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[9px] font-black uppercase tracking-wider bg-emerald-50 text-emerald-700 border border-emerald-200">
                                                <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 animate-pulse"></span>
                                                Live Now
                                            </span>
                                        @elseif($isScheduled)
                                            <span class="inline-flex items-center px-2.5 py-1 rounded-full text-[9px] font-black uppercase tracking-wider bg-blue-50 text-blue-700 border border-blue-200">
                                                Scheduled
                                            </span>
                                        @elseif($isExpired)
                                            <span class="inline-flex items-center px-2.5 py-1 rounded-full text-[9px] font-black uppercase tracking-wider bg-amber-50 text-amber-700 border border-amber-200">
                                                Expired
                                            </span>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        @endif
    </div>

</div>
@endsection
