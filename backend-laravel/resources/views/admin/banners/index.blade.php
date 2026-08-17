@extends('layouts.admin')

@section('content')
<div class="space-y-8" x-data="promotionManager({
    categories: {{ Js::from($categories) }},
    sellers: {{ Js::from($sellers) }},
    allProducts: {{ Js::from($allProducts) }},
    banners: {{ Js::from($banners) }}
})">

    {{-- Page Header --}}
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <div class="text-[10px] font-bold text-[#C0422A] uppercase tracking-[0.2em] mb-1">Content Management</div>
            <h1 class="font-serif text-2xl sm:text-3xl font-bold text-black">Homepage <span class="text-[#C0420A] font-light italic">Promotions</span></h1>
            <p class="text-xs text-gray-500 mt-1">Feature artisan products, shops, and seasonal hero campaigns on the marketplace homepage.</p>
        </div>
        <div class="flex items-center gap-2.5">
            <button @click="openAddModal()"
                class="flex items-center gap-2 px-5 sm:px-6 py-2.5 sm:py-3 bg-[#3D2B1F] text-white rounded-xl text-[9px] sm:text-[10px] font-bold uppercase tracking-widest hover:bg-[#C0422A] shadow-sm transition-all cursor-pointer">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                </svg>
                Add Promotion
            </button>
        </div>
    </div>

    {{-- Tabs --}}
    <div class="flex gap-2 border-b border-gray-100 pb-1">
        <button @click="tab = 'all'"
            :class="tab === 'all' ? 'border-b-2 border-black text-black font-black' : 'text-gray-500 font-bold hover:text-gray-700'"
            class="px-4 py-2 text-[10px] uppercase tracking-widest transition-all cursor-pointer">
            All Promotions ({{ $banners->count() }})
        </button>
        <button @click="tab = 'requests'"
            :class="tab === 'requests' ? 'border-b-2 border-black text-black font-black' : 'text-gray-500 font-bold hover:text-gray-700'"
            class="px-4 py-2 text-[10px] uppercase tracking-widest transition-all flex items-center gap-2 cursor-pointer">
            Seller Requests
            @if($pendingCount > 0)
                <span class="px-2 py-0.5 bg-amber-500 text-white text-[8px] font-black rounded-full animate-pulse">{{ $pendingCount }}</span>
            @endif
        </button>
    </div>

    {{-- ── ALL PROMOTIONS TAB ── --}}
    <div x-show="tab === 'all'" class="space-y-4">
        @if($banners->isEmpty())
            <div class="bg-white rounded-3xl border border-gray-100 p-16 text-center shadow-sm">
                <div class="w-16 h-16 bg-gray-50 rounded-full flex items-center justify-center mx-auto mb-4 border border-gray-100">
                    <svg class="w-8 h-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                    </svg>
                </div>
                <h3 class="text-sm font-bold text-black uppercase tracking-widest mb-1">No Promotions Created</h3>
                <p class="text-xs text-gray-500 max-w-sm mx-auto mb-6">Quickly feature products from shops or upload custom banner campaigns.</p>
                <button @click="openAddModal()" class="px-6 py-2.5 bg-[#3D2B1F] text-white rounded-xl text-xs font-bold uppercase tracking-widest hover:bg-[#C0422A] transition-all cursor-pointer">
                    Feature First Product / Promotion
                </button>
            </div>
        @else
            <div class="bg-white rounded-3xl border border-gray-100 overflow-hidden shadow-sm">
                <div class="p-4 bg-amber-50/40 border-b border-amber-100/60 flex items-center justify-between text-xs text-amber-900">
                    <div class="flex items-center gap-2">
                        <svg class="w-4 h-4 text-[#C0422A]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        <span><strong>Hero Carousel Order:</strong> Order <strong>#1</strong> appears first on the homepage. Use arrow buttons to reorder.</span>
                    </div>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="bg-gray-50/50">
                                <th class="px-5 py-4 text-[10px] font-black text-gray-700 uppercase tracking-widest text-center w-24">Order</th>
                                <th class="px-5 py-4 text-[10px] font-black text-gray-700 uppercase tracking-widest w-40">Preview</th>
                                <th class="px-5 py-4 text-[10px] font-black text-gray-700 uppercase tracking-widest">Promotion Details</th>
                                <th class="px-5 py-4 text-[10px] font-black text-gray-700 uppercase tracking-widest w-44">Schedule</th>
                                <th class="px-5 py-4 text-[10px] font-black text-gray-700 uppercase tracking-widest text-center w-28">Status</th>
                                <th class="px-5 py-4 text-[10px] font-black text-gray-700 uppercase tracking-widest text-right w-32">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-50">
                            @foreach($banners as $index => $banner)
                                @php
                                    $isLive = $banner->isCurrentlyLive();
                                    $now = now();
                                    $isScheduled = $banner->is_active && $banner->start_date && $banner->start_date > $now;
                                    $isExpired = $banner->is_active && $banner->end_date && $banner->end_date < $now;
                                @endphp
                                <tr class="hover:bg-gray-50/60 transition-colors group">
                                    {{-- Order Controls --}}
                                    <td class="px-5 py-4 text-center">
                                        <div class="flex items-center justify-center gap-1.5">
                                            <span class="w-6 h-6 rounded-lg bg-gray-100 text-gray-800 font-mono text-xs font-bold flex items-center justify-center">
                                                {{ $banner->order_index }}
                                            </span>
                                            <div class="flex flex-col gap-0.5">
                                                @if($index > 0)
                                                    <button type="button" @click="moveBanner({{ $index }}, 'up')" class="p-0.5 text-gray-400 hover:text-black transition-colors" title="Move Up">
                                                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 15l7-7 7 7"/></svg>
                                                    </button>
                                                @endif
                                                @if($index < count($banners) - 1)
                                                    <button type="button" @click="moveBanner({{ $index }}, 'down')" class="p-0.5 text-gray-400 hover:text-black transition-colors" title="Move Down">
                                                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 9l-7 7-7-7"/></svg>
                                                    </button>
                                                @endif
                                            </div>
                                        </div>
                                    </td>

                                    {{-- Preview Image --}}
                                    <td class="px-5 py-4">
                                        <div class="w-36 aspect-16/9 rounded-xl overflow-hidden bg-gray-900 border border-gray-100 shadow-xs relative">
                                            <img src="{{ $banner->getImageUrl() }}" class="w-full h-full object-cover" alt="Banner">
                                            <div class="absolute inset-0 bg-linear-to-r from-black/60 to-transparent"></div>
                                        </div>
                                    </td>

                                    {{-- Details --}}
                                    <td class="px-5 py-4">
                                        <div class="space-y-1">
                                            @if($banner->subtitle)
                                                <div class="text-[9px] font-bold text-amber-600 uppercase tracking-widest">{{ $banner->subtitle }}</div>
                                            @endif
                                            <div class="text-sm font-extrabold text-gray-900">{{ $banner->title ?: 'Untitled Promotion' }}</div>
                                            
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
                                    <td class="px-5 py-4 text-center">
                                        @if(!$banner->is_active)
                                            <span class="inline-flex items-center px-2.5 py-1 rounded-full text-[9px] font-black uppercase tracking-wider bg-gray-100 text-gray-600 border border-gray-200">
                                                Hidden
                                            </span>
                                        @elseif($isLive)
                                            <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[9px] font-black uppercase tracking-wider bg-green-50 text-green-700 border border-green-200">
                                                <span class="w-1.5 h-1.5 rounded-full bg-green-500 animate-pulse"></span>
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

                                    {{-- Actions --}}
                                    <td class="px-5 py-4 text-right">
                                        <div class="flex items-center justify-end gap-1">
                                            <form action="{{ route('admin.banners.toggle', $banner->id) }}" method="POST" class="inline">
                                                @csrf @method('PATCH')
                                                <button type="submit" class="p-2 text-gray-400 hover:text-black transition-colors" title="{{ $banner->is_active ? 'Hide Promotion' : 'Show Promotion' }}">
                                                    @if($banner->is_active)
                                                        <svg class="w-4 h-4 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                                                    @else
                                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l18 18"/></svg>
                                                    @endif
                                                </button>
                                            </form>

                                            <button @click="openEditModal({{ Js::from($banner) }})"
                                                class="p-2 text-gray-500 hover:text-black transition-colors cursor-pointer" title="Edit Promotion">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"/>
                                                </svg>
                                            </button>

                                            <form action="{{ route('admin.banners.destroy', $banner->id) }}" method="POST" class="inline"
                                                onsubmit="return confirm('Are you sure you want to delete this promotion?')">
                                                @csrf @method('DELETE')
                                                <button type="submit" class="p-2 text-gray-400 hover:text-red-600 transition-colors cursor-pointer" title="Delete Promotion">
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                                    </svg>
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        @endif
    </div>

    {{-- ── SELLER REQUESTS TAB ── --}}
    <div x-show="tab === 'requests'" class="space-y-4" style="display:none;" x-cloak>
        @if($sellerBanners->isEmpty())
            <div class="bg-white rounded-3xl border border-gray-100 p-12 text-center shadow-sm">
                <div class="w-14 h-14 bg-gray-50 rounded-full flex items-center justify-center mx-auto mb-4 border border-gray-100">
                    <svg class="w-7 h-7 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
                <p class="text-xs font-bold text-gray-500 uppercase tracking-widest">No seller promotion requests yet</p>
            </div>
        @else
            <div class="bg-white rounded-3xl border border-gray-100 shadow-sm overflow-hidden divide-y divide-gray-50">
                @foreach($sellerBanners as $banner)
                    <div class="p-6 flex flex-col md:flex-row md:items-center gap-6 hover:bg-gray-50/50 transition-all">
                        {{-- Preview --}}
                        <div class="w-36 shrink-0">
                            <div class="w-36 aspect-16/9 rounded-xl overflow-hidden bg-gray-900 border border-gray-100 shadow-sm relative">
                                <img src="{{ $banner->getImageUrl() }}" class="w-full h-full object-cover">
                            </div>
                        </div>

                        {{-- Details --}}
                        <div class="flex-1 space-y-1">
                            <div class="flex items-center gap-2 flex-wrap">
                                <span class="text-sm font-bold text-black">{{ $banner->title ?: 'Untitled Promotion' }}</span>
                                @if($banner->status === 'pending')
                                    <span class="px-2 py-0.5 bg-amber-50 border border-amber-200 text-amber-700 text-[8px] font-black rounded-full uppercase tracking-wider">Pending</span>
                                @elseif($banner->status === 'approved')
                                    <span class="px-2 py-0.5 bg-green-50 border border-green-200 text-green-700 text-[8px] font-black rounded-full uppercase tracking-wider">Approved</span>
                                @else
                                    <span class="px-2 py-0.5 bg-red-50 border border-red-200 text-red-700 text-[8px] font-black rounded-full uppercase tracking-wider">Rejected</span>
                                @endif
                            </div>
                            <p class="text-[10px] text-gray-600 max-w-md leading-relaxed">{{ Str::limit($banner->subtitle, 100) }}</p>
                            @if($banner->user)
                                <div class="flex items-center gap-1.5 text-[9px] text-gray-500 font-bold uppercase tracking-wider pt-1">
                                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                                    {{ $banner->user->shopName ?: $banner->user->name }}
                                    <span class="text-gray-300">·</span>
                                    {{ $banner->created_at->diffForHumans() }}
                                </div>
                            @endif
                            @if($banner->button_text_1)
                                <div class="text-[9px] text-gray-500 pt-0.5">Btn 1: <span class="font-bold text-gray-700">{{ $banner->button_text_1 }}</span> → {{ $banner->button_url_1 }}</div>
                            @endif
                            @if($banner->button_text_2)
                                <div class="text-[9px] text-gray-500">Btn 2: <span class="font-bold text-gray-700">{{ $banner->button_text_2 }}</span> → {{ $banner->button_url_2 }}</div>
                            @endif
                            @if($banner->rejection_reason)
                                <div class="text-[9px] text-red-600 font-bold pt-1">Rejection Reason: {{ $banner->rejection_reason }}</div>
                            @endif
                        </div>

                        {{-- Actions --}}
                        <div class="flex items-center gap-2 shrink-0">
                            @if($banner->status === 'pending')
                                <form action="{{ route('admin.banners.approve', $banner->id) }}" method="POST">
                                    @csrf @method('PATCH')
                                    <button type="submit"
                                        class="px-4 py-2.5 bg-black hover:bg-green-600 text-white rounded-xl text-[9px] font-black uppercase tracking-widest transition-all cursor-pointer">
                                        Approve
                                    </button>
                                </form>
                                <button type="button"
                                    @click="rejectRoute = '{{ route('admin.banners.reject', $banner->id) }}'; rejectBannerTitle = '{{ addslashes($banner->title ?: 'Untitled') }}'; showRejectModal = true"
                                    class="px-4 py-2.5 bg-white border border-red-200 text-red-600 hover:bg-red-50 rounded-xl text-[9px] font-black uppercase tracking-widest transition-all cursor-pointer">
                                    Reject
                                </button>
                            @elseif($banner->status === 'approved')
                                <form action="{{ route('admin.banners.toggle', $banner->id) }}" method="POST">
                                    @csrf @method('PATCH')
                                    <button type="submit"
                                        class="px-3 py-1.5 rounded-full text-[9px] font-black uppercase tracking-widest transition-all cursor-pointer {{ $banner->is_active ? 'bg-green-50 text-green-700 border border-green-200 hover:bg-green-100' : 'bg-gray-100 text-gray-600 border border-gray-200 hover:bg-gray-200' }}">
                                        {{ $banner->is_active ? 'Live' : 'Hidden' }}
                                    </button>
                                </form>
                            @endif
                            <form action="{{ route('admin.banners.destroy', $banner->id) }}" method="POST"
                                onsubmit="return confirm('Delete this promotion request?')">
                                @csrf @method('DELETE')
                                <button type="submit" class="p-2 text-gray-400 hover:text-red-600 transition-colors cursor-pointer">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                    </svg>
                                </button>
                            </form>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </div>

    {{-- ── PROMOTION FORM MODAL (Add & Edit with LIVE PREVIEW) ── --}}
    <div x-show="showModal" class="fixed inset-0 z-50 flex items-center justify-center p-3 sm:p-6 bg-black/60 backdrop-blur-sm overflow-y-auto" x-cloak>
        <div class="bg-white rounded-3xl w-full max-w-4xl shadow-2xl border border-gray-100 overflow-hidden flex flex-col my-auto max-h-[94vh]"
            @click.away="showModal = false">
            
            {{-- Modal Top Header --}}
            <div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between bg-gray-50/80 shrink-0">
                <div class="flex items-center gap-2">
                    <span class="w-2.5 h-2.5 rounded-full bg-[#C0422A]"></span>
                    <h2 class="font-serif text-lg sm:text-xl font-bold text-gray-900" x-text="isEditing ? 'Edit Hero Promotion' : 'Add Hero Promotion'"></h2>
                </div>
                <button type="button" @click="showModal = false" class="text-gray-400 hover:text-black transition-colors p-1">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>

            {{-- Modal Body (Scrollable) --}}
            <div class="p-6 overflow-y-auto space-y-6">
                
                {{-- ── 1. LIVE PREVIEW CARD ── --}}
                <div class="bg-gray-950 rounded-2xl p-4 border border-gray-800 shadow-inner">
                    <div class="flex items-center justify-between mb-3">
                        <div class="flex items-center gap-2">
                            <span class="text-[10px] font-black uppercase tracking-[0.2em] text-amber-400">✨ Live Hero Preview</span>
                            <span class="text-[9px] text-gray-400">(Real-time simulation)</span>
                        </div>
                        <div class="flex items-center bg-gray-900 rounded-lg p-0.5 border border-gray-800 text-[10px]">
                            <button type="button" @click="previewMode = 'desktop'" 
                                :class="previewMode === 'desktop' ? 'bg-[#C0422A] text-white font-bold' : 'text-gray-400 hover:text-white'"
                                class="px-3 py-1 rounded-md transition-all cursor-pointer">
                                🖥️ Desktop (3:1)
                            </button>
                            <button type="button" @click="previewMode = 'mobile'" 
                                :class="previewMode === 'mobile' ? 'bg-[#C0422A] text-white font-bold' : 'text-gray-400 hover:text-white'"
                                class="px-3 py-1 rounded-md transition-all cursor-pointer">
                                📱 Mobile View
                            </button>
                        </div>
                    </div>

                    {{-- Live Simulation Container --}}
                    <div class="flex justify-center">
                        <div :class="previewMode === 'desktop' ? 'w-full aspect-16/5 sm:aspect-16/4 min-h-[170px]' : 'w-72 aspect-4/5 sm:w-80'"
                             class="relative rounded-2xl overflow-hidden bg-gray-900 border border-gray-800 shadow-lg transition-all duration-300 flex flex-col justify-center">
                            
                            {{-- Preview Image --}}
                            <template x-if="imagePreviewUrl">
                                <img :src="imagePreviewUrl" class="absolute inset-0 w-full h-full object-cover">
                            </template>
                            <template x-if="!imagePreviewUrl">
                                <div class="absolute inset-0 w-full h-full bg-linear-to-r from-gray-900 via-gray-800 to-gray-900 flex items-center justify-center text-gray-600 text-xs font-mono">
                                    [ Pick a product or upload image to preview ]
                                </div>
                            </template>

                            {{-- Gradient Overlay --}}
                            <div class="absolute inset-0 bg-linear-to-r from-black/90 via-black/55 to-transparent"></div>

                            {{-- Preview Text Content --}}
                            <div class="relative z-10 p-5 sm:p-8 flex flex-col justify-center h-full max-w-sm sm:max-w-md">
                                <p x-show="form.subtitle" x-text="form.subtitle" class="text-[9px] sm:text-[10px] font-black uppercase tracking-[0.25em] text-amber-300 mb-1.5 line-clamp-1"></p>
                                <h3 x-text="form.title || 'Promotion Headline'" class="text-base sm:text-2xl font-extrabold text-white leading-tight mb-3 line-clamp-2"></h3>
                                
                                <div class="flex flex-wrap items-center gap-2 mt-1">
                                    <template x-if="form.button_text_1">
                                        <span class="px-3 sm:px-4 py-1.5 bg-[#C0422A] text-white text-[9px] sm:text-[10px] font-black uppercase tracking-wider rounded-lg shadow-sm" x-text="form.button_text_1"></span>
                                    </template>
                                    <template x-if="form.button_text_2">
                                        <span class="px-3 sm:px-4 py-1.5 bg-white/20 backdrop-blur-md text-white border border-white/40 text-[9px] sm:text-[10px] font-black uppercase tracking-wider rounded-lg" x-text="form.button_text_2"></span>
                                    </template>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- ── 2. QUICK CREATION MODE SWITCHER ── --}}
                <div x-show="!isEditing" class="p-1 bg-gray-100 rounded-2xl flex gap-1 border border-gray-200">
                    <button type="button" @click="creationMode = 'product'"
                        :class="creationMode === 'product' ? 'bg-white text-gray-900 font-extrabold shadow-xs' : 'text-gray-500 font-bold hover:text-gray-800'"
                        class="flex-1 py-2.5 rounded-xl text-xs flex items-center justify-center gap-2 transition-all cursor-pointer">
                        <span>🛍️ Feature an Existing Product & Shop</span>
                        <span class="px-1.5 py-0.5 bg-amber-100 text-amber-800 text-[8px] font-black rounded-md uppercase">Fastest</span>
                    </button>
                    <button type="button" @click="creationMode = 'custom'"
                        :class="creationMode === 'custom' ? 'bg-white text-gray-900 font-extrabold shadow-xs' : 'text-gray-500 font-bold hover:text-gray-800'"
                        class="flex-1 py-2.5 rounded-xl text-xs flex items-center justify-center gap-2 transition-all cursor-pointer">
                        <span>🎨 Custom Banner / Upload Image</span>
                    </button>
                </div>

                {{-- ── 3. PROMOTION FORM ── --}}
                <form :action="isEditing ? '/admin/banners/' + form.id : '{{ route('admin.banners.store') }}'"
                      method="POST"
                      enctype="multipart/form-data"
                      id="bannerForm"
                      class="space-y-5">
                    @csrf
                    <template x-if="isEditing">
                        <input type="hidden" name="_method" value="PUT">
                    </template>
                    <input type="hidden" name="preset_image_url" :value="form.preset_image_url">

                    {{-- QUICK PRODUCT SELECTOR BOX --}}
                    <div x-show="creationMode === 'product' && !isEditing" class="p-5 bg-amber-50/50 border-2 border-dashed border-amber-200 rounded-2xl space-y-4">
                        <div class="flex items-center justify-between">
                            <span class="text-[10px] font-black uppercase tracking-wider text-amber-900 flex items-center gap-1.5">
                                <span class="w-2 h-2 rounded-full bg-[#C0422A]"></span>
                                Select Artisan Shop & Product
                            </span>
                            <span class="text-[9px] text-amber-800 font-medium">Auto-fills title, image, shop link, and buy link</span>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                            {{-- Pick Shop --}}
                            <div>
                                <label class="text-[9px] font-bold text-gray-700 uppercase tracking-wider block mb-1">1. Choose Artisan Shop</label>
                                <select x-model="selectedShopId" @change="onShopSelected()" class="w-full px-3.5 py-2.5 bg-white border border-gray-200 rounded-xl text-xs text-gray-900 focus:ring-2 focus:ring-[#C0422A]/20">
                                    <option value="">-- All Shops / Select Shop --</option>
                                    <template x-for="s in sellers" :key="s.id">
                                        <option :value="s.id" x-text="s.shop_name + ' (' + (s.products ? s.products.length : 0) + ' products)'"></option>
                                    </template>
                                </select>
                            </div>

                            {{-- Pick Product --}}
                            <div>
                                <label class="text-[9px] font-bold text-gray-700 uppercase tracking-wider block mb-1">2. Choose Product to Feature</label>
                                <select x-model="selectedProductId" @change="onProductSelected()" class="w-full px-3.5 py-2.5 bg-white border border-gray-200 rounded-xl text-xs text-gray-900 focus:ring-2 focus:ring-[#C0422A]/20">
                                    <option value="">-- Select Product --</option>
                                    <template x-for="p in selectableProducts" :key="p.id">
                                        <option :value="p.id" x-text="p.name + ' (₱' + Number(p.price).toLocaleString() + ')'"></option>
                                    </template>
                                </select>
                            </div>
                        </div>

                        {{-- Quick Info Card when product selected --}}
                        <div x-show="selectedProduct" class="p-3 bg-white rounded-xl border border-amber-200/80 flex items-center justify-between gap-3">
                            <div class="flex items-center gap-3 min-w-0">
                                <img :src="selectedProduct ? selectedProduct.image_url : ''" class="w-12 h-12 rounded-lg object-cover border border-gray-200 shrink-0">
                                <div class="min-w-0">
                                    <div class="text-xs font-bold text-gray-900 truncate" x-text="selectedProduct ? selectedProduct.name : ''"></div>
                                    <div class="text-[10px] text-[#C0422A] font-bold" x-text="selectedProduct ? 'Shop: ' + selectedProduct.shop_name : ''"></div>
                                </div>
                            </div>
                            <span class="px-2.5 py-1 bg-green-50 text-green-700 border border-green-200 rounded-lg text-[9px] font-black uppercase shrink-0">
                                ✓ Links Auto-Configured
                            </span>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                        
                        {{-- Image Upload & Override --}}
                        <div class="md:col-span-2 space-y-2">
                            <div class="flex items-center justify-between">
                                <label class="text-[10px] font-bold text-gray-700 uppercase tracking-widest block">
                                    Promotion Banner Image <span x-show="!isEditing && !form.preset_image_url" class="text-red-500">*</span>
                                </label>
                                <span class="text-[9px] font-bold text-gray-400 uppercase tracking-wider">Recommended: 1200 × 400 px (3:1 ratio)</span>
                            </div>

                            <div class="flex items-center gap-4 p-4 bg-gray-50 border border-gray-200 rounded-2xl">
                                <div class="w-20 h-14 bg-gray-200 rounded-xl overflow-hidden shrink-0 border border-gray-300 flex items-center justify-center">
                                    <template x-if="imagePreviewUrl">
                                        <img :src="imagePreviewUrl" class="w-full h-full object-cover">
                                    </template>
                                    <template x-if="!imagePreviewUrl">
                                        <svg class="w-6 h-6 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                                    </template>
                                </div>
                                <div class="flex-1 min-w-0">
                                    <input type="file" name="image" @change="handleFileSelect($event)" :required="!isEditing && !form.preset_image_url"
                                        accept="image/jpeg,image/png,image/jpg,image/webp,image/gif"
                                        class="block w-full text-xs text-gray-700 file:mr-3 file:py-2 file:px-4 file:rounded-xl file:border-0 file:text-[10px] file:font-bold file:uppercase file:tracking-wider file:bg-[#3D2B1F] file:text-white hover:file:bg-[#C0422A] file:cursor-pointer cursor-pointer">
                                    <div x-show="fileInfo.name" class="text-[10px] text-gray-500 mt-1.5 flex items-center gap-2">
                                        <span class="truncate font-medium" x-text="fileInfo.name"></span>
                                        <span class="text-gray-300">·</span>
                                        <span x-text="fileInfo.size"></span>
                                        <span x-show="fileInfo.dimensions" class="text-gray-300">·</span>
                                        <span x-show="fileInfo.dimensions" class="text-[#C0422A] font-bold" x-text="fileInfo.dimensions"></span>
                                    </div>
                                    <div x-show="form.preset_image_url && !fileInfo.name" class="text-[10px] text-green-700 font-medium mt-1">
                                        Using high-resolution product image. (You can upload a custom wide banner above to override).
                                    </div>
                                </div>
                            </div>
                        </div>

                        {{-- Title / Main Text with Counter --}}
                        <div>
                            <div class="flex items-center justify-between mb-1.5">
                                <label class="text-[10px] font-bold text-gray-700 uppercase tracking-widest">Main Headline / Product Title</label>
                                <span class="text-[9px] font-mono" :class="form.title.length > 50 ? (form.title.length > 60 ? 'text-red-500 font-bold' : 'text-amber-500 font-bold') : 'text-gray-400'" x-text="form.title.length + ' / 60'"></span>
                            </div>
                            <input type="text" name="title" x-model="form.title" maxlength="60"
                                placeholder="e.g. Handcrafted Piña Silk Barong"
                                class="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl text-sm text-gray-900 focus:outline-none focus:ring-2 focus:ring-[#C0422A]/20 transition-all">
                        </div>

                        {{-- Subtitle / Shop Badge with Counter --}}
                        <div>
                            <div class="flex items-center justify-between mb-1.5">
                                <label class="text-[10px] font-bold text-gray-700 uppercase tracking-widest">Shop Name / Subtitle Badge</label>
                                <span class="text-[9px] font-mono" :class="form.subtitle.length > 85 ? (form.subtitle.length > 100 ? 'text-red-500 font-bold' : 'text-amber-500 font-bold') : 'text-gray-400'" x-text="form.subtitle.length + ' / 100'"></span>
                            </div>
                            <input type="text" name="subtitle" x-model="form.subtitle" maxlength="100"
                                placeholder="e.g. MACAPAGAL EMBROIDERY"
                                class="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl text-sm text-gray-900 focus:outline-none focus:ring-2 focus:ring-[#C0422A]/20 transition-all">
                        </div>

                        {{-- ── BUTTON 1: BUY / SHOP PRODUCT ── --}}
                        <div class="p-4 bg-gray-50 border border-gray-200 rounded-2xl space-y-3">
                            <div class="flex items-center justify-between">
                                <span class="text-[10px] font-black uppercase tracking-wider text-gray-800 flex items-center gap-1.5">
                                    <span class="w-2 h-2 rounded-full bg-[#C0422A]"></span>
                                    Primary Button (Buy / View Product)
                                </span>
                            </div>
                            
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                <div>
                                    <label class="text-[9px] font-bold text-gray-500 uppercase tracking-wider block mb-1">Button Label</label>
                                    <input type="text" name="button_text_1" x-model="form.button_text_1" placeholder="e.g. Shop Now" maxlength="30"
                                        class="w-full px-3 py-2 bg-white border border-gray-200 rounded-lg text-xs text-gray-900 focus:ring-2 focus:ring-[#C0422A]/20">
                                </div>
                                <div>
                                    <label class="text-[9px] font-bold text-gray-500 uppercase tracking-wider block mb-1">Destination URL</label>
                                    <input type="text" name="button_url_1" x-model="form.button_url_1" placeholder="e.g. /products/... or /#catalogue-section"
                                        class="w-full px-3 py-2 bg-white border border-gray-200 rounded-lg text-xs text-gray-900 font-mono focus:ring-2 focus:ring-[#C0422A]/20">
                                </div>
                            </div>
                        </div>

                        {{-- ── BUTTON 2: VISIT SHOP ── --}}
                        <div class="p-4 bg-gray-50 border border-gray-200 rounded-2xl space-y-3">
                            <div class="flex items-center justify-between">
                                <span class="text-[10px] font-black uppercase tracking-wider text-gray-800 flex items-center gap-1.5">
                                    <span class="w-2 h-2 rounded-full bg-gray-400"></span>
                                    Secondary Button (Visit Shop)
                                </span>
                            </div>
                            
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                <div>
                                    <label class="text-[9px] font-bold text-gray-500 uppercase tracking-wider block mb-1">Button Label</label>
                                    <input type="text" name="button_text_2" x-model="form.button_text_2" placeholder="e.g. Visit Shop" maxlength="30"
                                        class="w-full px-3 py-2 bg-white border border-gray-200 rounded-lg text-xs text-gray-900 focus:ring-2 focus:ring-[#C0422A]/20">
                                </div>
                                <div>
                                    <label class="text-[9px] font-bold text-gray-500 uppercase tracking-wider block mb-1">Destination URL</label>
                                    <input type="text" name="button_url_2" x-model="form.button_url_2" placeholder="e.g. /shops/... or #"
                                        class="w-full px-3 py-2 bg-white border border-gray-200 rounded-lg text-xs text-gray-900 font-mono focus:ring-2 focus:ring-[#C0422A]/20">
                                </div>
                            </div>
                        </div>

                        {{-- Display Order & Status (Simple) --}}
                        <div>
                            <label class="text-[10px] font-bold text-gray-700 uppercase tracking-widest block mb-1.5">Display Order Position</label>
                            <input type="number" name="order_index" x-model="form.order_index" min="1" step="1"
                                class="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl text-sm text-gray-900 font-mono">
                            <p class="text-[9px] text-gray-500 mt-1">1 = First banner in carousel. Automatically shifts others safely.</p>
                        </div>

                        <div class="flex items-center">
                            <label class="flex items-center gap-2.5 p-3.5 bg-gray-50 border border-gray-200 rounded-xl cursor-pointer w-full mt-auto">
                                <input type="checkbox" name="is_active" value="1" x-model="form.is_active" class="w-4 h-4 rounded border-gray-300 text-[#C0422A] focus:ring-[#C0422A]/20">
                                <div>
                                    <div class="text-xs font-bold text-gray-800 uppercase tracking-wider">Active Visibility</div>
                                    <div class="text-[9px] text-gray-500">Enable this promotion to show when within schedule</div>
                                </div>
                            </label>
                        </div>

                        {{-- ── 4. COLLAPSIBLE ADVANCED & SCHEDULING ── --}}
                        <div class="md:col-span-2 border border-gray-200 rounded-2xl overflow-hidden bg-white">
                            <button type="button" @click="showAdvanced = !showAdvanced"
                                class="w-full px-5 py-3.5 bg-gray-50 hover:bg-gray-100 flex items-center justify-between text-xs font-bold text-gray-800 uppercase tracking-wider transition-colors cursor-pointer">
                                <span class="flex items-center gap-2">
                                    <svg class="w-4 h-4 text-[#C0422A]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                                    Advanced Options & Scheduling
                                </span>
                                <svg class="w-4 h-4 text-gray-500 transform transition-transform duration-200" :class="showAdvanced ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                            </button>

                            <div x-show="showAdvanced" class="p-5 space-y-4 border-t border-gray-100 bg-white" style="display:none;" x-cloak>
                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                    <div>
                                        <label class="text-[10px] font-bold text-gray-700 uppercase tracking-widest block mb-1">Start Date & Time (Optional)</label>
                                        <input type="datetime-local" name="start_date" x-model="form.start_date"
                                            class="w-full px-3.5 py-2.5 bg-gray-50 border border-gray-200 rounded-xl text-xs text-gray-800">
                                        <p class="text-[9px] text-gray-400 mt-1">Leave empty to activate immediately.</p>
                                    </div>
                                    <div>
                                        <label class="text-[10px] font-bold text-gray-700 uppercase tracking-widest block mb-1">End Date & Time (Optional)</label>
                                        <input type="datetime-local" name="end_date" x-model="form.end_date"
                                            class="w-full px-3.5 py-2.5 bg-gray-50 border border-gray-200 rounded-xl text-xs text-gray-800">
                                        <p class="text-[9px] text-gray-400 mt-1">Leave empty to run continuously.</p>
                                    </div>
                                </div>
                            </div>
                        </div>

                    </div>

                    {{-- Form Bottom Actions --}}
                    <div class="flex items-center gap-3 pt-4 border-t border-gray-100">
                        <button type="button" @click="showModal = false" class="flex-1 px-6 py-3 bg-gray-100 text-gray-700 rounded-xl text-[10px] font-bold uppercase tracking-widest hover:bg-gray-200 transition-all cursor-pointer">
                            Cancel
                        </button>
                        <button type="submit" class="flex-1 px-6 py-3 bg-[#3D2B1F] text-white rounded-xl text-[10px] font-bold uppercase tracking-widest hover:bg-[#C0422A] shadow-md transition-all cursor-pointer">
                            <span x-text="isEditing ? 'Save Promotion Changes' : 'Publish Hero Promotion'"></span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    {{-- Reject Modal --}}
    <div x-show="showRejectModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50 backdrop-blur-sm" x-cloak>
        <div class="bg-white rounded-3xl w-full max-w-md p-6 shadow-2xl" @click.away="showRejectModal = false">
            <h3 class="font-serif text-lg font-bold text-black mb-2">Reject Banner Request</h3>
            <p class="text-xs text-gray-500 mb-4">Please provide a reason for rejecting "<span class="font-bold text-black" x-text="rejectBannerTitle"></span>".</p>
            <form :action="rejectRoute" method="POST" class="space-y-4">
                @csrf @method('PATCH')
                <textarea name="rejection_reason" rows="3" required placeholder="e.g. Image resolution is too low..."
                    class="w-full px-3 py-2 bg-gray-50 border border-gray-200 rounded-xl text-xs text-gray-900 focus:outline-none focus:ring-2 focus:ring-red-500/20"></textarea>
                <div class="flex gap-2">
                    <button type="button" @click="showRejectModal = false" class="flex-1 py-2.5 bg-gray-100 text-gray-700 rounded-xl text-[10px] font-bold uppercase tracking-widest">Cancel</button>
                    <button type="submit" class="flex-1 py-2.5 bg-red-600 hover:bg-red-700 text-white rounded-xl text-[10px] font-bold uppercase tracking-widest">Confirm Rejection</button>
                </div>
            </form>
        </div>
    </div>

</div>

<script>
function promotionManager(initialData) {
    return {
        tab: '{{ $pendingCount > 0 ? "requests" : "all" }}',
        showModal: false,
        showRejectModal: false,
        showAdvanced: false,
        isEditing: false,
        creationMode: 'product', // 'product' or 'custom'
        rejectRoute: '',
        rejectBannerTitle: '',
        previewMode: 'desktop',
        imagePreviewUrl: '',
        fileInfo: { name: '', size: '', dimensions: '' },
        
        categories: initialData.categories || [],
        sellers: initialData.sellers || [],
        allProducts: initialData.allProducts || [],
        bannersList: initialData.banners || [],

        selectedShopId: '',
        selectedProductId: '',
        selectedProduct: null,

        get selectableProducts() {
            if (!this.selectedShopId) {
                return this.allProducts;
            }
            var self = this;
            return this.allProducts.filter(function(p) {
                return p.seller_id === self.selectedShopId;
            });
        },

        form: {
            id: '',
            title: '',
            subtitle: '',
            button_text_1: 'Shop Now',
            button_url_1: '',
            button_text_2: 'Visit Shop',
            button_url_2: '',
            order_index: 1,
            is_active: true,
            start_date: '',
            end_date: '',
            preset_image_url: ''
        },

        onShopSelected() {
            this.selectedProductId = '';
            this.selectedProduct = null;
            if (this.selectedShopId) {
                var shop = this.sellers.find(s => s.id === this.selectedShopId);
                if (shop) {
                    this.form.subtitle = (shop.shop_name || shop.name).toUpperCase();
                    this.form.button_url_2 = '/shops/' + shop.id;
                }
            }
        },

        onProductSelected() {
            if (!this.selectedProductId) {
                this.selectedProduct = null;
                return;
            }
            var product = this.allProducts.find(p => p.id === this.selectedProductId);
            if (!product) return;

            this.selectedProduct = product;
            this.selectedShopId = product.seller_id;
            
            // Auto-fill Title, Subtitle, and Buttons
            this.form.title = product.name;
            this.form.subtitle = product.shop_name ? product.shop_name.toUpperCase() : 'ARTISAN BARONG';
            this.form.button_text_1 = 'Shop Now';
            this.form.button_url_1 = '/products/' + product.id;
            this.form.button_text_2 = 'Visit Shop';
            this.form.button_url_2 = '/shops/' + product.seller_id;
            
            // Auto-set high-res product image for hero preview
            if (product.image_url) {
                this.imagePreviewUrl = product.image_url;
                this.form.preset_image_url = product.image_url;
                this.fileInfo = { name: '', size: '', dimensions: '' };
            }
        },

        openAddModal() {
            this.isEditing = false;
            this.creationMode = 'product';
            this.imagePreviewUrl = '';
            this.fileInfo = { name: '', size: '', dimensions: '' };
            this.showAdvanced = false;
            this.selectedShopId = '';
            this.selectedProductId = '';
            this.selectedProduct = null;

            this.form = {
                id: '',
                title: '',
                subtitle: '',
                button_text_1: 'Shop Now',
                button_url_1: '',
                button_text_2: 'Visit Shop',
                button_url_2: '',
                order_index: (this.bannersList.length + 1),
                is_active: true,
                start_date: '',
                end_date: '',
                preset_image_url: ''
            };

            this.showModal = true;
        },

        openEditModal(banner) {
            this.isEditing = true;
            this.creationMode = 'custom';
            this.showAdvanced = (banner.start_date || banner.end_date);
            this.imagePreviewUrl = banner.image_path ? (banner.image_path.startsWith('http') || banner.image_path.startsWith('/') ? banner.image_path : '/' + banner.image_path) : '';
            this.fileInfo = { name: 'Current Image', size: '', dimensions: '' };
            
            var formatDt = function(dtStr) {
                if (!dtStr) return '';
                var d = new Date(dtStr);
                if (isNaN(d.getTime())) return '';
                return d.toISOString().slice(0, 16);
            };

            this.form = {
                id: banner.id,
                title: banner.title || '',
                subtitle: banner.subtitle || '',
                button_text_1: banner.button_text_1 || 'Shop Now',
                button_url_1: banner.button_url_1 || '',
                button_text_2: banner.button_text_2 || 'Visit Shop',
                button_url_2: banner.button_url_2 || '',
                order_index: banner.order_index || 1,
                is_active: Boolean(banner.is_active),
                start_date: formatDt(banner.start_date),
                end_date: formatDt(banner.end_date),
                preset_image_url: ''
            };

            this.showModal = true;
        },

        handleFileSelect(event) {
            var file = event.target.files[0];
            if (!file) return;

            var self = this;
            self.fileInfo.name = file.name;
            var sizeKb = Math.round(file.size / 1024);
            self.fileInfo.size = sizeKb > 1024 ? (sizeKb / 1024).toFixed(1) + ' MB' : sizeKb + ' KB';

            var reader = new FileReader();
            reader.onload = function(e) {
                self.imagePreviewUrl = e.target.result;
                self.form.preset_image_url = '';
                var img = new Image();
                img.onload = function() {
                    self.fileInfo.dimensions = img.width + ' × ' + img.height + ' px';
                };
                img.src = e.target.result;
            };
            reader.readAsDataURL(file);
        },

        moveBanner(index, direction) {
            var newIndex = direction === 'up' ? index - 1 : index + 1;
            if (newIndex < 0 || newIndex >= this.bannersList.length) return;

            var item = this.bannersList.splice(index, 1)[0];
            this.bannersList.splice(newIndex, 0, item);

            var orderedIds = this.bannersList.map(function(b) { return b.id; });
            var token = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

            fetch('{{ route("admin.banners.reorder") }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': token
                },
                body: JSON.stringify({ ordered_ids: orderedIds })
            })
            .then(function(res) { return res.json(); })
            .then(function() {
                window.location.reload();
            })
            .catch(function(err) {
                console.error('Reorder error', err);
            });
        }
    };
}
</script>
@endsection