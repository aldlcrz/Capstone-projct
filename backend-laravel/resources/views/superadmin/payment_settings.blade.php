@extends('layouts.superadmin')

@section('content')
<div class="space-y-6">

    <!-- Header Banner -->
    <div class="bg-white border border-[#E5DDD5] rounded-3xl p-6 lg:p-8 shadow-sm flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
            <div class="flex items-center gap-2 text-xs font-bold text-[#C0422A] uppercase tracking-widest mb-1">
                <span>💳 Official Payment Gateways</span>
            </div>
            <h1 class="font-serif text-2xl lg:text-3xl font-bold text-[#3D2B1F]">Payment Settings &amp; QR Codes</h1>
            <p class="text-xs text-gray-500 mt-1">Configure official GCash and Maya mobile numbers and QR code images shown to frozen sellers when paying overdue commissions.</p>
        </div>
    </div>

    <!-- Alert Notifications -->
    @if(session('success'))
    <div class="p-4 bg-green-50 border border-green-200 text-green-700 text-xs font-bold rounded-2xl flex items-center justify-between shadow-sm">
        <div class="flex items-center gap-2">
            <svg class="w-5 h-5 text-green-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
            </svg>
            <span>{{ session('success') }}</span>
        </div>
    </div>
    @endif

    @if($errors->any())
    <div class="p-4 bg-red-50 border border-red-200 text-red-700 text-xs font-bold rounded-2xl space-y-1 shadow-sm">
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
    <div class="bg-white border border-[#E5DDD5] rounded-3xl p-6 lg:p-8 shadow-sm space-y-6">
        <div class="border-b border-[#E5DDD5] pb-4">
            <h2 class="text-base font-bold text-[#3D2B1F] uppercase tracking-wider flex items-center gap-2">
                <span>📱 Official Payment Accounts</span>
            </h2>
            <p class="text-xs text-gray-500 mt-1">Sellers with frozen accounts will view these numbers and scan these QR codes to pay overdue system commissions.</p>
        </div>

        <form action="{{ route('superadmin.payment-settings.update') }}" method="POST" enctype="multipart/form-data" class="space-y-8">
            @csrf
            <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                
                <!-- GCash Settings Card -->
                <div class="bg-[#F9F6F2] border border-[#E5DDD5] rounded-2xl p-6 space-y-5">
                    <div class="flex items-center justify-between border-b border-[#E5DDD5] pb-3">
                        <span class="text-xs font-black text-blue-600 uppercase tracking-wider flex items-center gap-2">
                            <span class="w-3 h-3 rounded-full bg-blue-500"></span> GCash Gateway
                        </span>
                        @if($gcashQr)
                            <span class="text-[10px] bg-green-100 text-green-700 px-2.5 py-1 rounded-full font-bold border border-green-200">QR Code Active</span>
                        @else
                            <span class="text-[10px] bg-amber-100 text-amber-700 px-2.5 py-1 rounded-full font-bold border border-amber-200">No QR Code Uploaded</span>
                        @endif
                    </div>

                    <div class="space-y-2">
                        <label class="block text-[10px] font-bold text-gray-500 uppercase tracking-widest">GCash Mobile Number</label>
                        <input type="text" name="gcash_number" value="{{ old('gcash_number', $gcashNumber) }}" placeholder="e.g. 0917 123 4567"
                               class="w-full px-4 py-3 bg-white border border-[#E5DDD5] rounded-xl text-sm font-bold text-[#3D2B1F] focus:outline-none focus:border-[#C0422A] shadow-sm">
                    </div>

                    <div class="space-y-2">
                        <label class="block text-[10px] font-bold text-gray-500 uppercase tracking-widest">GCash QR Code Image</label>
                        <div class="flex items-center gap-4">
                            @if($gcashQr)
                                <div class="shrink-0 text-center">
                                    <img src="{{ asset('storage/' . $gcashQr) }}" class="w-20 h-20 object-cover rounded-xl border border-[#E5DDD5] shadow-sm bg-white p-1">
                                    <span class="text-[9px] text-gray-400 block mt-1">Current QR</span>
                                </div>
                            @endif
                            <div class="flex-1">
                                <input type="file" name="gcash_qr" accept="image/*"
                                       class="w-full text-xs text-gray-500 file:mr-4 file:py-2.5 file:px-4 file:rounded-xl file:border-0 file:text-xs file:font-bold file:bg-[#3D2B1F] file:text-white hover:file:bg-[#C0422A] file:transition-all">
                                <p class="text-[10px] text-gray-400 mt-1">Supports PNG, JPG, or JPEG up to 2MB.</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Maya Settings Card -->
                <div class="bg-[#F9F6F2] border border-[#E5DDD5] rounded-2xl p-6 space-y-5">
                    <div class="flex items-center justify-between border-b border-[#E5DDD5] pb-3">
                        <span class="text-xs font-black text-emerald-600 uppercase tracking-wider flex items-center gap-2">
                            <span class="w-3 h-3 rounded-full bg-emerald-500"></span> Maya Gateway
                        </span>
                        @if($mayaQr)
                            <span class="text-[10px] bg-green-100 text-green-700 px-2.5 py-1 rounded-full font-bold border border-green-200">QR Code Active</span>
                        @else
                            <span class="text-[10px] bg-amber-100 text-amber-700 px-2.5 py-1 rounded-full font-bold border border-amber-200">No QR Code Uploaded</span>
                        @endif
                    </div>

                    <div class="space-y-2">
                        <label class="block text-[10px] font-bold text-gray-500 uppercase tracking-widest">Maya Mobile Number</label>
                        <input type="text" name="maya_number" value="{{ old('maya_number', $mayaNumber) }}" placeholder="e.g. 0918 987 6543"
                               class="w-full px-4 py-3 bg-white border border-[#E5DDD5] rounded-xl text-sm font-bold text-[#3D2B1F] focus:outline-none focus:border-[#C0422A] shadow-sm">
                    </div>

                    <div class="space-y-2">
                        <label class="block text-[10px] font-bold text-gray-500 uppercase tracking-widest">Maya QR Code Image</label>
                        <div class="flex items-center gap-4">
                            @if($mayaQr)
                                <div class="shrink-0 text-center">
                                    <img src="{{ asset('storage/' . $mayaQr) }}" class="w-20 h-20 object-cover rounded-xl border border-[#E5DDD5] shadow-sm bg-white p-1">
                                    <span class="text-[9px] text-gray-400 block mt-1">Current QR</span>
                                </div>
                            @endif
                            <div class="flex-1">
                                <input type="file" name="maya_qr" accept="image/*"
                                       class="w-full text-xs text-gray-500 file:mr-4 file:py-2.5 file:px-4 file:rounded-xl file:border-0 file:text-xs file:font-bold file:bg-[#3D2B1F] file:text-white hover:file:bg-[#C0422A] file:transition-all">
                                <p class="text-[10px] text-gray-400 mt-1">Supports PNG, JPG, or JPEG up to 2MB.</p>
                            </div>
                        </div>
                    </div>
                </div>

            </div>

            <div class="flex justify-end pt-4 border-t border-[#E5DDD5]">
                <button type="submit" class="px-8 py-3.5 bg-[#3D2B1F] hover:bg-[#C0422A] text-white text-xs font-bold uppercase tracking-widest rounded-xl transition-all shadow-md">
                    Save Payment Gateways &amp; QR Codes
                </button>
            </div>
        </form>
    </div>

</div>
@endsection
