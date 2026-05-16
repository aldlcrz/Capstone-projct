@extends('layouts.app')

@section('content')
<div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
    <div class="flex items-center justify-between mb-12">
        <div>
            <h1 class="text-4xl font-serif font-bold text-gray-900 mb-2">Notifications</h1>
            <p class="text-gray-500">Stay updated on your heritage journey.</p>
        </div>
        <div class="flex items-center gap-2 px-4 py-2 bg-gray-50 rounded-full border border-gray-100">
            <div class="w-2 h-2 rounded-full bg-rust animate-pulse"></div>
            <span class="text-[10px] font-black uppercase tracking-widest text-gray-400">Live Updates</span>
        </div>
    </div>

    @if($notifications->count() > 0)
    <div class="space-y-4">
        @foreach($notifications as $notification)
        <div class="group bg-white p-6 rounded-3xl border border-gray-100 shadow-sm hover:shadow-md transition-all duration-300 {{ !$notification->isRead ? 'border-l-4 border-l-rust' : '' }}">
            <div class="flex items-start gap-5">
                <!-- Icon based on type -->
                <div class="w-12 h-12 rounded-2xl flex items-center justify-center shrink-0 
                    {{ $notification->type === 'order' ? 'bg-blue-50 text-blue-500' : '' }}
                    {{ $notification->type === 'promo' ? 'bg-amber-50 text-amber-500' : '' }}
                    {{ $notification->type === 'system' ? 'bg-gray-50 text-gray-500' : '' }}
                    {{ !$notification->type || !in_array($notification->type, ['order','promo','system']) ? 'bg-rust/10 text-rust' : '' }}">
                    @if($notification->type === 'order')
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"></path></svg>
                    @elseif($notification->type === 'promo')
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v13m0-13V6a2 2 0 112 2h-2zm0 0V5a2 2 0 10-2 2h2z"></path></svg>
                    @else
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"></path></svg>
                    @endif
                </div>

                <div class="flex-1 min-w-0">
                    <div class="flex items-center justify-between mb-1">
                        <h3 class="text-sm font-bold text-gray-900 truncate">
                            {{ $notification->title }}
                        </h3>
                        <span class="text-[10px] font-bold text-gray-400">
                            {{ $notification->createdAt->diffForHumans() }}
                        </span>
                    </div>
                    <p class="text-sm text-gray-500 leading-relaxed">
                        {{ $notification->message }}
                    </p>
                    
                    @if($notification->link)
                    <a href="{{ $notification->link }}" class="inline-flex items-center gap-1 mt-4 text-[10px] font-black uppercase tracking-widest text-rust hover:text-black transition-colors">
                        View Details
                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path></svg>
                    </a>
                    @endif
                </div>
            </div>
        </div>
        @endforeach
    </div>

    <div class="mt-12">
        {{ $notifications->links() }}
    </div>
    @else
    <div class="text-center py-24 bg-gray-50 rounded-3xl border border-dashed border-gray-200">
        <div class="w-20 h-20 bg-white rounded-full flex items-center justify-center mx-auto mb-6 shadow-sm">
            <svg class="w-10 h-10 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"></path></svg>
        </div>
        <h2 class="text-2xl font-serif font-bold text-gray-900 mb-2">No notifications yet</h2>
        <p class="text-gray-500 mb-8 max-w-xs mx-auto">We'll let you know when something important happens.</p>
        <a href="{{ route('home') }}" class="inline-flex px-8 py-4 bg-black text-white rounded-full text-xs font-black uppercase tracking-widest hover:bg-rust transition-all duration-300 shadow-xl shadow-black/10">
            Explore Shop
        </a>
    </div>
    @endif
</div>
@endsection
