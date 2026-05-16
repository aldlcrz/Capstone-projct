@extends('layouts.seller')

@section('content')
<div class="max-w-[900px] mx-auto space-y-8">
    <div>
        <div class="text-[10px] font-bold text-[#C0420A] uppercase tracking-[0.2em] mb-1">Artisan Account</div>
        <h1 class="font-serif text-3xl font-bold text-black uppercase">Seller <span class="text-[#C0420A] italic lowercase">profile</span></h1>
    </div>

    @if(session('success'))
        <div class="px-6 py-4 bg-green-50 border border-green-100 rounded-2xl text-green-700 text-xs font-bold flex items-center gap-3">
            <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
            {{ session('success') }}
        </div>
    @endif

    @if($errors->any())
        <div class="px-6 py-4 bg-red-50 border border-red-100 rounded-2xl space-y-1">
            @foreach($errors->all() as $e)
                <div class="text-xs text-red-600 font-bold">• {{ $e }}</div>
            @endforeach
        </div>
    @endif

    <form action="{{ route('seller.profile.update') }}" method="POST" enctype="multipart/form-data"
        class="bg-white rounded-3xl border border-gray-100 shadow-sm overflow-hidden">
        @csrf
        @method('PUT')

        {{-- Profile Header --}}
        <div class="bg-linear-to-r from-[#3D2B1F] to-[#C0420A] p-8 flex items-center gap-6">
            <div class="w-20 h-20 rounded-2xl bg-white/20 border-2 border-white/30 flex items-center justify-center text-white font-black text-2xl overflow-hidden">
                @if($user->profilePhoto)
                    <img src="{{ $user->profilePhoto }}" class="w-full h-full object-cover">
                @else
                    {{ strtoupper(substr($user->name, 0, 1)) }}
                @endif
            </div>
            <div>
                <div class="text-white font-bold text-lg">{{ $user->name }}</div>
                <div class="text-white/60 text-[10px] uppercase tracking-widest font-bold">{{ $user->email }}</div>
                <div class="mt-2 flex items-center gap-2">
                    @if($user->isVerified)
                        <span class="px-2 py-0.5 bg-green-400/20 text-green-300 border border-green-300/30 rounded text-[9px] font-black uppercase tracking-widest">✓ Verified Artisan</span>
                    @else
                        <span class="px-2 py-0.5 bg-amber-400/20 text-amber-300 border border-amber-300/30 rounded text-[9px] font-black uppercase tracking-widest">⏳ Pending Verification</span>
                    @endif
                </div>
            </div>
        </div>

        <div class="p-8 space-y-8">
            {{-- Basic Info --}}
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div class="space-y-2">
                    <label class="text-[10px] font-bold uppercase tracking-widest text-gray-400">Full Name</label>
                    <input type="text" name="name" required value="{{ old('name', $user->name) }}"
                        class="w-full px-5 py-3.5 bg-gray-50 border border-gray-100 rounded-xl outline-none focus:border-[#C0420A] transition-all text-sm font-medium">
                </div>
                <div class="space-y-2">
                    <label class="text-[10px] font-bold uppercase tracking-widest text-gray-400">Mobile Number</label>
                    <input type="text" name="mobileNumber" value="{{ old('mobileNumber', $user->mobileNumber) }}"
                        placeholder="+63 9XX XXX XXXX"
                        class="w-full px-5 py-3.5 bg-gray-50 border border-gray-100 rounded-xl outline-none focus:border-[#C0420A] transition-all text-sm font-medium">
                </div>
                <div class="space-y-2">
                    <label class="text-[10px] font-bold uppercase tracking-widest text-gray-400">Email Address</label>
                    <div class="px-5 py-3.5 bg-gray-50 rounded-xl border border-gray-100 text-sm text-gray-400 font-medium">
                        {{ $user->email }} <span class="ml-2 text-[9px] text-green-500 font-black uppercase">Verified</span>
                    </div>
                </div>
                <div class="space-y-2">
                    <label class="text-[10px] font-bold uppercase tracking-widest text-gray-400">Shop Name</label>
                    <input type="text" name="shopName" value="{{ old('shopName', $user->shopName ?? '') }}"
                        placeholder="Your artisan shop name"
                        class="w-full px-5 py-3.5 bg-gray-50 border border-gray-100 rounded-xl outline-none focus:border-[#C0420A] transition-all text-sm font-medium">
                </div>
            </div>

            <div class="space-y-2">
                <label class="text-[10px] font-bold uppercase tracking-widest text-gray-400">Shop Description</label>
                <textarea name="shopDescription" rows="3" placeholder="Describe your artisan workshop and heritage crafts..."
                    class="w-full px-5 py-3.5 bg-gray-50 border border-gray-100 rounded-xl outline-none focus:border-[#C0420A] transition-all text-sm font-medium resize-none">{{ old('shopDescription', $user->shopDescription ?? '') }}</textarea>
            </div>

            {{-- Stats Row --}}
            <div class="grid grid-cols-3 gap-4 pt-4 border-t border-gray-50">
                <div class="text-center p-4 bg-gray-50/50 rounded-2xl">
                    <div class="text-2xl font-black text-black">{{ $stats['products'] }}</div>
                    <div class="text-[9px] font-bold text-gray-400 uppercase tracking-widest mt-1">Products</div>
                </div>
                <div class="text-center p-4 bg-gray-50/50 rounded-2xl">
                    <div class="text-2xl font-black text-[#C0420A]">{{ $stats['orders'] }}</div>
                    <div class="text-[9px] font-bold text-gray-400 uppercase tracking-widest mt-1">Total Orders</div>
                </div>
                <div class="text-center p-4 bg-gray-50/50 rounded-2xl">
                    <div class="text-2xl font-black text-black">₱{{ number_format($stats['revenue']) }}</div>
                    <div class="text-[9px] font-bold text-gray-400 uppercase tracking-widest mt-1">Revenue</div>
                </div>
            </div>

            <div class="flex items-center justify-between pt-4 border-t border-gray-50">
                <p class="text-[10px] text-gray-400">Member since {{ $user->createdAt->format('F Y') }}</p>
                <button type="submit"
                    class="px-10 py-4 bg-black text-white rounded-full font-bold uppercase tracking-[0.2em] text-[10px] shadow-xl hover:bg-[#C0420A] transition-all">
                    Save Profile
                </button>
            </div>
        </div>
    </form>
</div>
@endsection
