@extends('layouts.app')

@section('content')
<div class="max-w-[1200px] mx-auto py-8">
    <div class="flex flex-col md:flex-row gap-12">
        
        <!-- Sidebar -->
        <aside class="w-full md:w-64 space-y-8">
            <div class="flex items-center gap-4 px-2">
                <div class="w-14 h-14 rounded-full bg-gray-50 border border-gray-100 flex items-center justify-center overflow-hidden">
                    <span class="text-xl font-bold text-gray-300">{{ strtoupper(substr($user->name, 0, 1)) }}</span>
                </div>
                <div>
                    <div class="text-sm font-bold text-black truncate max-w-[120px]">{{ $user->name }}</div>
                    <div class="text-[10px] font-bold text-gray-400 uppercase tracking-widest">Heritage Member</div>
                </div>
            </div>

            <nav class="space-y-1">
                <a href="/profile" class="flex items-center gap-3 px-4 py-3 text-sm font-bold rounded-xl {{ request()->is('profile') ? 'bg-black text-white' : 'text-gray-500 hover:bg-gray-50 hover:text-black' }} transition-all">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path></svg>
                    Profile Settings
                </a>
                <a href="/orders/my-orders" class="flex items-center gap-3 px-4 py-3 text-sm font-bold text-gray-500 rounded-xl hover:bg-gray-50 hover:text-black transition-all">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"></path></svg>
                    My Purchase
                </a>
            </nav>
        </aside>

        <!-- Main Content -->
        <main class="flex-1 bg-white rounded-3xl border border-gray-100 p-8 sm:p-12 shadow-sm">
            <div class="border-b border-gray-50 pb-8 mb-10">
                <h2 class="text-2xl font-bold text-black">My Profile</h2>
                <p class="text-sm text-gray-400 mt-1">Manage and protect your heritage account</p>
            </div>

            @if(session('success'))
                <div class="mb-8 px-5 py-4 bg-green-50 border border-green-100 rounded-2xl text-green-700 text-xs font-bold flex items-center gap-3">
                    <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                    {{ session('success') }}
                </div>
            @endif

            <form action="/profile" method="POST" class="max-w-2xl space-y-8">
                @csrf
                @method('PUT')

                <div class="grid grid-cols-1 sm:grid-cols-4 gap-4 items-center">
                    <label class="sm:text-right text-xs font-bold uppercase tracking-widest text-gray-400 pr-4">Full Name</label>
                    <div class="sm:col-span-3">
                        <input type="text" name="name" value="{{ old('name', $user->name) }}" class="w-full h-12 bg-gray-50 border-2 border-transparent focus:border-black focus:bg-white rounded-xl px-6 text-sm font-medium outline-none transition-all">
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-4 gap-4 items-center">
                    <label class="sm:text-right text-xs font-bold uppercase tracking-widest text-gray-400 pr-4">Email Address</label>
                    <div class="sm:col-span-3">
                        <div class="flex items-center gap-2">
                            <span class="text-sm font-bold text-black">{{ $user->email }}</span>
                            <span class="px-2 py-0.5 bg-green-50 text-green-600 text-[9px] font-black uppercase tracking-widest border border-green-100 rounded">Verified</span>
                        </div>
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-4 gap-4 items-center">
                    <label class="sm:text-right text-xs font-bold uppercase tracking-widest text-gray-400 pr-4">Mobile No.</label>
                    <div class="sm:col-span-3">
                        <input type="text" name="mobileNumber" value="{{ old('mobileNumber', $user->mobileNumber) }}" class="w-full h-12 bg-gray-50 border-2 border-transparent focus:border-black focus:bg-white rounded-xl px-6 text-sm font-medium outline-none transition-all" placeholder="Enter your mobile number">
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-4 gap-4 items-center">
                    <div class="hidden sm:block"></div>
                    <div class="sm:col-span-3 pt-4">
                        <button type="submit" class="px-10 py-4 bg-black text-white rounded-full font-bold uppercase tracking-[0.2em] text-[10px] shadow-xl shadow-black/10 hover:bg-gray-900 transition-all">
                            Save Changes
                        </button>
                    </div>
                </div>
            </form>
        </main>
    </div>
</div>
@endsection
