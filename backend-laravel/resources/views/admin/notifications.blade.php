@extends('layouts.admin')

@section('content')
<div class="space-y-8">
    <div class="flex items-center justify-between">
        <div>
            <div class="text-[10px] font-bold text-[#C0420A] uppercase tracking-[0.2em] mb-1">System Alerts</div>
            <h1 class="font-serif text-3xl font-bold text-black uppercase">Notifications</h1>
        </div>
        @if($notifications->count() > 0)
            <div class="flex items-center gap-3">
                <form action="{{ route('admin.notifications.read-all') }}" method="POST">
                    @csrf
                    <button type="submit" class="flex items-center gap-2 px-6 py-3 bg-white text-gray-700 rounded-xl text-[10px] font-bold uppercase tracking-widest border border-gray-200 hover:bg-gray-50 transition-all shadow-sm">
                        <svg class="w-4 h-4 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                        Mark All as Read
                    </button>
                </form>
            </div>
        @endif
    </div>

    @if($notifications->count() > 0)
        <div class="space-y-4">
            @foreach($notifications as $notification)
                <div class="group bg-white p-6 rounded-2xl border border-gray-100 shadow-sm hover:shadow-md transition-all duration-300 {{ !$notification->isRead ? 'border-l-4 border-l-[#C0420A]' : '' }}">
                    <div class="flex items-start gap-5">
                        <div class="w-12 h-12 rounded-xl flex items-center justify-center shrink-0 bg-red-50 text-[#C0420A]">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"></path></svg>
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
                            <p class="text-xs text-gray-500 leading-relaxed">
                                {{ $notification->message }}
                            </p>
                            
                            @if($notification->link)
                                <a href="{{ $notification->link }}" class="inline-flex items-center gap-1 mt-4 text-[10px] font-bold uppercase tracking-widest text-[#C0420A] hover:text-black transition-colors">
                                    View Details
                                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path></svg>
                                </a>
                            @endif
                        </div>
                    </div>
                </div>
            @endforeach
        </div>

        <div class="mt-8">
            {{ $notifications->links() }}
        </div>
    @else
        <div class="text-center py-24 bg-white rounded-3xl border border-gray-100 shadow-sm">
            <div class="w-20 h-20 bg-gray-50 rounded-full flex items-center justify-center mx-auto mb-6 border border-gray-100 shadow-inner">
                <svg class="w-10 h-10 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"></path></svg>
            </div>
            <h2 class="font-serif text-2xl font-bold text-gray-900 mb-2">No notifications yet</h2>
            <p class="text-xs text-gray-500 mb-8 max-w-xs mx-auto">We'll let you know when system events occur.</p>
            <a href="/admin/dashboard" class="inline-flex px-8 py-4 bg-black text-white rounded-xl text-[10px] font-bold uppercase tracking-widest hover:bg-[#C0420A] transition-all shadow-lg shadow-black/5">
                Dashboard Home
            </a>
        </div>
    @endif
</div>
@endsection
