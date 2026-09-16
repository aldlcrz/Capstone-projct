@extends('layouts.superadmin')

@section('content')
<div class="space-y-6">

    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <div class="text-[10px] font-bold text-[#C0422A] uppercase tracking-[0.2em] mb-1">Financial Gateways</div>
            <h1 class="font-serif text-2xl sm:text-3xl font-bold text-[#3D2B1F]">
                Payment <span class="text-[#C0422A] italic">Settings</span>
            </h1>
            <p class="text-xs text-gray-500 mt-1">Configure official GCash and Maya mobile numbers and QR code images shown to sellers when settling commissions.</p>
        </div>
    </div>

    <!-- Alert Notifications -->
    @if(session('success'))
    <div class="p-4 bg-emerald-50 border border-emerald-200 text-emerald-800 text-xs font-bold rounded-2xl flex items-center gap-3 shadow-xs">
        <svg class="w-5 h-5 text-emerald-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
        </svg>
        <span>{{ session('success') }}</span>
    </div>
    @endif

    @if($errors->any())
    <div class="p-4 bg-red-50 border border-red-200 text-red-700 text-xs font-bold rounded-2xl space-y-1 shadow-xs">
        @foreach($errors->all() as $error)
            <div class="flex items-center gap-2">
                <svg class="w-4 h-4 text-red-500 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
                <span>{{ $error }}</span>
            </div>
        @endforeach
    </div>
    @endif

    <!-- Payment Gateway Form Card -->
    <div class="bg-white border border-[#E5DDD5] rounded-3xl p-6 lg:p-8 shadow-xs space-y-6">
        <div class="border-b border-gray-100 pb-4">
            <span class="text-[10px] font-black uppercase tracking-widest text-[#C0422A]">System Settlement Channels</span>
            <h2 class="font-serif text-lg font-bold text-[#3D2B1F]">Official Receiving Accounts</h2>
            <p class="text-xs text-gray-500 mt-0.5">Sellers with frozen accounts or outstanding balances will scan these QR codes to upload proof of payment.</p>
        </div>

        <form action="{{ route('superadmin.payment-settings.update') }}" method="POST" enctype="multipart/form-data" class="space-y-8">
            @csrf
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                
                <!-- GCash Settings Card -->
                <div class="bg-[#F7F3EE] border border-[#E5DDD5] rounded-2xl p-6 space-y-5">
                    <div class="flex items-center justify-between border-b border-[#E5DDD5] pb-3">
                        <span class="text-xs font-black text-blue-600 uppercase tracking-wider flex items-center gap-2">
                            <span class="w-2.5 h-2.5 rounded-full bg-blue-500"></span> GCash Gateway
                        </span>
                        @if($gcashQr)
                            <span class="text-[10px] bg-emerald-50 text-emerald-700 px-2.5 py-1 rounded-full font-bold border border-emerald-200">QR Code Active</span>
                        @else
                            <span class="text-[10px] bg-amber-50 text-amber-700 px-2.5 py-1 rounded-full font-bold border border-amber-200">No QR Uploaded</span>
                        @endif
                    </div>

                    <div class="space-y-2">
                        <label class="block text-[10px] font-bold text-gray-600 uppercase tracking-widest">GCash Mobile Number</label>
                        <input type="text" name="gcash_number" value="{{ old('gcash_number', $gcashNumber) }}" placeholder="e.g. 0917 123 4567"
                               class="w-full px-4 py-2.5 bg-white border border-gray-200 rounded-xl text-xs font-bold text-[#3D2B1F] focus:outline-none focus:border-[#C0422A] shadow-xs">
                    </div>

                    <div class="space-y-2">
                        <label class="block text-[10px] font-bold text-gray-600 uppercase tracking-widest">GCash QR Code Image</label>
                        <div class="flex items-center gap-4">
                            @if($gcashQr)
                                @php
                                    $cleanGcash = ltrim($gcashQr, '/');
                                    $gcashUrl = str_starts_with($cleanGcash, 'uploads/') ? asset($cleanGcash) : (str_starts_with($cleanGcash, 'storage/') ? asset($cleanGcash) : asset('storage/' . $cleanGcash));
                                @endphp
                                <div class="shrink-0 text-center">
                                    <img src="{{ $gcashUrl }}" class="w-20 h-20 object-cover rounded-xl border border-[#E5DDD5] shadow-xs bg-white p-1" alt="GCash QR">
                                    <span class="text-[9px] text-gray-400 block mt-1">Current QR</span>
                                </div>
                            @endif
                            <div class="flex-1">
                                <input type="file" name="gcash_qr" accept="image/*"
                                       class="w-full text-xs text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-xl file:border-0 file:text-xs file:font-bold file:bg-[#3D2B1F] file:text-white hover:file:bg-[#C0422A] file:cursor-pointer transition-all">
                                <p class="text-[10px] text-gray-400 mt-1">Supports PNG, JPG, or JPEG up to 2MB.</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Maya Settings Card -->
                <div class="bg-[#F7F3EE] border border-[#E5DDD5] rounded-2xl p-6 space-y-5">
                    <div class="flex items-center justify-between border-b border-[#E5DDD5] pb-3">
                        <span class="text-xs font-black text-emerald-600 uppercase tracking-wider flex items-center gap-2">
                            <span class="w-2.5 h-2.5 rounded-full bg-emerald-500"></span> Maya Gateway
                        </span>
                        @if($mayaQr)
                            <span class="text-[10px] bg-emerald-50 text-emerald-700 px-2.5 py-1 rounded-full font-bold border border-emerald-200">QR Code Active</span>
                        @else
                            <span class="text-[10px] bg-amber-50 text-amber-700 px-2.5 py-1 rounded-full font-bold border border-amber-200">No QR Uploaded</span>
                        @endif
                    </div>

                    <div class="space-y-2">
                        <label class="block text-[10px] font-bold text-gray-600 uppercase tracking-widest">Maya Mobile Number</label>
                        <input type="text" name="maya_number" value="{{ old('maya_number', $mayaNumber) }}" placeholder="e.g. 0918 987 6543"
                               class="w-full px-4 py-2.5 bg-white border border-gray-200 rounded-xl text-xs font-bold text-[#3D2B1F] focus:outline-none focus:border-[#C0422A] shadow-xs">
                    </div>

                    <div class="space-y-2">
                        <label class="block text-[10px] font-bold text-gray-600 uppercase tracking-widest">Maya QR Code Image</label>
                        <div class="flex items-center gap-4">
                            @if($mayaQr)
                                @php
                                    $cleanMaya = ltrim($mayaQr, '/');
                                    $mayaUrl = str_starts_with($cleanMaya, 'uploads/') ? asset($cleanMaya) : (str_starts_with($cleanMaya, 'storage/') ? asset($cleanMaya) : asset('storage/' . $cleanMaya));
                                @endphp
                                <div class="shrink-0 text-center">
                                    <img src="{{ $mayaUrl }}" class="w-20 h-20 object-cover rounded-xl border border-[#E5DDD5] shadow-xs bg-white p-1" alt="Maya QR">
                                    <span class="text-[9px] text-gray-400 block mt-1">Current QR</span>
                                </div>
                            @endif
                            <div class="flex-1">
                                <input type="file" name="maya_qr" accept="image/*"
                                       class="w-full text-xs text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-xl file:border-0 file:text-xs file:font-bold file:bg-[#3D2B1F] file:text-white hover:file:bg-[#C0422A] file:cursor-pointer transition-all">
                                <p class="text-[10px] text-gray-400 mt-1">Supports PNG, JPG, or JPEG up to 2MB.</p>
                            </div>
                        </div>
                    </div>
                </div>

            </div>

            <div class="flex justify-end pt-4 border-t border-gray-100">
                <button type="submit" class="px-6 py-2.5 bg-[#3D2B1F] hover:bg-[#C0422A] text-white text-xs font-bold uppercase tracking-widest rounded-xl transition-all shadow-xs cursor-pointer">
                    Save Payment Gateways &amp; QR Codes
                </button>
            </div>
        </form>
    </div>

</div>
@endsection
