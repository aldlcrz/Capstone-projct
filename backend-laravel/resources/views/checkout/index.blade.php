@extends('layouts.app')

@section('content')
<div class="max-w-[1200px] mx-auto px-4 py-12 min-h-screen bg-white" x-data="{
    step: 1,
    paymentMethod: 'GCash',
    address: @js($addresses->first() ?? [
        'recipientName' => '',
        'phone' => '',
        'houseNo' => '',
        'street' => '',
        'barangay' => '',
        'city' => '',
        'province' => '',
        'postalCode' => ''
    ]),
    showAddressModal: false,
    addresses: @js($addresses),
    
    selectAddress(addr) {
        this.address = addr;
        this.showAddressModal = false;
    }
}">
    <!-- Stepper -->
    <div class="flex items-center gap-12 mb-12 border-b border-gray-100 pb-2 overflow-x-auto no-scrollbar">
        <div class="flex items-center gap-3 border-b-2 pb-2 transition-colors" :class="step >= 1 ? 'border-black' : 'border-transparent text-gray-300'">
            <div class="w-6 h-6 rounded-full flex items-center justify-center" :class="step > 1 ? 'bg-gray-400' : 'bg-black'">
                <template x-if="step > 1">
                    <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                </template>
                <template x-if="step === 1">
                    <span class="text-[11px] font-bold text-white">1</span>
                </template>
            </div>
            <span class="text-sm font-bold whitespace-nowrap" :class="step >= 1 ? 'text-black' : 'text-gray-300'">Shipping Information</span>
        </div>
        <div class="flex items-center gap-3 border-b-2 pb-2 transition-colors" :class="step >= 2 ? 'border-black' : 'border-transparent text-gray-300'">
            <div class="w-6 h-6 rounded-full flex items-center justify-center" :class="step >= 2 ? 'bg-black' : 'border border-gray-300'">
                <span class="text-[11px] font-bold" :class="step >= 2 ? 'text-white' : 'text-gray-300'">2</span>
            </div>
            <span class="text-sm font-bold whitespace-nowrap" :class="step >= 2 ? 'text-black' : 'text-gray-300'">Payment Details</span>
        </div>
    </div>

    <form action="{{ route('checkout.store') }}" method="POST" enctype="multipart/form-data">
        @csrf
        <input type="hidden" name="mode" value="{{ $mode }}">
        <input type="hidden" name="shippingAddress" :value="JSON.stringify(address)">

        <div class="grid grid-cols-1 lg:grid-cols-12 gap-12 items-start">
            <!-- Main Content Area -->
            <div class="lg:col-span-7 space-y-12">
                
                <!-- STEP 1: SHIPPING -->
                <div x-show="step === 1" x-transition>
                    <div class="mb-8">
                        <h1 class="text-2xl font-bold text-black mb-2">Check Out Your Items</h1>
                        <p class="text-sm text-gray-400 font-medium">Verify your shipping details before proceeding.</p>
                    </div>

                    <div class="space-y-6">
                        <div class="relative border-2 rounded-xl p-4 transition-all border-gray-100 focus-within:border-black">
                            <label class="text-[10px] font-bold text-gray-400 uppercase tracking-widest mb-1 block">Full Name</label>
                            <input type="text" x-model="address.recipientName" class="w-full font-bold text-black outline-none bg-transparent" placeholder="Full Name">
                        </div>

                        <button type="button" @click="showAddressModal = true" class="w-full relative border-2 border-gray-100 rounded-xl p-6 text-left hover:border-black transition-colors group cursor-pointer">
                            <div class="flex items-start justify-between">
                                <div class="flex gap-4">
                                    <svg class="w-5 h-5 text-gray-400 mt-1 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"></path></svg>
                                    <div>
                                        <label class="text-[10px] font-bold text-gray-400 uppercase tracking-widest block">Delivery Address</label>
                                        <p class="font-medium text-gray-500 mt-1" x-text="address.houseNo ? address.houseNo + ' ' + address.street + ', ' + address.barangay + ', ' + address.city : 'Select an address'"></p>
                                    </div>
                                </div>
                                <svg class="w-4 h-4 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"></path></svg>
                            </div>
                        </button>

                        <div class="relative border-2 rounded-xl p-4 transition-all border-gray-100 focus-within:border-black">
                            <label class="text-[10px] font-bold text-gray-400 uppercase tracking-widest mb-1 block">Phone Number</label>
                            <input type="text" x-model="address.phone" class="w-full font-bold text-black outline-none bg-transparent" placeholder="Phone Number">
                        </div>
                    </div>
                </div>

                <!-- STEP 2: PAYMENT -->
                <div x-show="step === 2" x-transition>
                    <div class="mb-8">
                        <h2 class="text-xl font-bold text-black mb-1">Payment Method</h2>
                        <p class="text-sm text-gray-400 font-medium">Select your preferred payment option.</p>
                    </div>

                    <div class="space-y-4">
                        <div class="rounded-2xl border-2 p-6 transition-all" :class="paymentMethod === 'GCash' ? 'border-black bg-white' : 'border-gray-50'">
                            <label class="flex items-center justify-between cursor-pointer">
                                <div class="flex items-center gap-4">
                                    <div class="bg-blue-50 text-blue-600 px-3 py-1 rounded-lg text-xs font-black">GCASH</div>
                                    <span class="font-bold text-gray-700">GCash</span>
                                </div>
                                <input type="radio" name="paymentMethod" value="GCash" x-model="paymentMethod" class="w-5 h-5 accent-black">
                            </label>
                            
                            <div x-show="paymentMethod === 'GCash'" class="mt-6 pt-6 border-t border-gray-50 flex gap-6">
                                <div class="w-1/3 bg-gray-50 rounded-2xl p-4 flex flex-col items-center justify-center">
                                    <div class="w-24 h-24 bg-white rounded-xl mb-2 flex items-center justify-center text-gray-200">
                                        <svg class="w-12 h-12" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 0h.01M5 8h2a1 1 0 001-1V5a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1zm12 0h2a1 1 0 001-1V5a1 1 0 00-1-1h-2a1 1 0 00-1 1v2a1 1 0 001 1zM5 20h2a1 1 0 001-1v-2a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1z"></path></svg>
                                    </div>
                                    <span class="text-[9px] font-black uppercase text-gray-400">Scan QR</span>
                                </div>
                                <div class="flex-1 space-y-4">
                                    <div class="bg-blue-50/50 p-4 rounded-xl border border-blue-100">
                                        <div class="text-[9px] font-black text-blue-400 uppercase tracking-widest mb-1">Send Payment To</div>
                                        <div class="text-xl font-black text-black">0912 345 6789</div>
                                        <div class="text-[10px] font-bold text-gray-500 mt-1">Name: LumBarong Store</div>
                                    </div>
                                    <div class="space-y-3">
                                        <input type="text" name="paymentReference" placeholder="Reference Number" class="w-full px-4 py-3 bg-[#F9F7F4] border-gray-100 border rounded-xl text-sm font-bold outline-none focus:border-black">
                                        <input type="file" name="paymentScreenshot" class="w-full text-xs text-gray-400 file:bg-black file:text-white file:rounded-lg file:border-0 file:px-4 file:py-2 file:mr-4 file:font-black">
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Maya Option (Similar structure) -->
                        <div class="rounded-2xl border-2 p-6 transition-all" :class="paymentMethod === 'Maya' ? 'border-black bg-white' : 'border-gray-50'">
                            <label class="flex items-center justify-between cursor-pointer">
                                <div class="flex items-center gap-4">
                                    <div class="bg-green-50 text-green-600 px-3 py-1 rounded-lg text-xs font-black">MAYA</div>
                                    <span class="font-bold text-gray-700">Maya</span>
                                </div>
                                <input type="radio" name="paymentMethod" value="Maya" x-model="paymentMethod" class="w-5 h-5 accent-black">
                            </label>
                        </div>
                    </div>
                </div>

            </div>

            <!-- Sidebar -->
            <div class="lg:col-span-5">
                <div class="bg-[#F9FAFB] rounded-[40px] p-8 border border-gray-100 sticky top-10">
                    <h2 class="text-xl font-bold mb-6">Order Summary</h2>
                    <div class="space-y-4 mb-8 max-h-[300px] overflow-y-auto pr-2 custom-scrollbar">
                        @foreach($cart as $item)
                            <div class="bg-white rounded-2xl p-4 flex gap-4 border border-gray-50 shadow-sm">
                                <div class="w-16 h-16 bg-gray-50 rounded-xl overflow-hidden shrink-0">
                                    <img src="{{ asset('storage/' . ($item['image'] ?? 'products/default.jpg')) }}" class="w-full h-full object-cover">
                                </div>
                                <div class="flex-1 min-w-0">
                                    <h3 class="text-xs font-bold text-black line-clamp-1">{{ $item['name'] }}</h3>
                                    <div class="flex justify-between items-center mt-2">
                                        <span class="text-[10px] text-gray-400 font-bold">{{ $item['quantity'] }}x</span>
                                        <span class="text-xs font-bold text-black">₱{{ number_format($item['price'] * $item['quantity']) }}</span>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>

                    <div class="space-y-3 border-t border-gray-100 pt-6 mb-8">
                        <div class="flex justify-between items-center">
                            <span class="text-sm text-gray-400 font-medium">Subtotal</span>
                            <span class="text-sm font-bold text-black">₱{{ number_format($subtotal) }}</span>
                        </div>
                        <div class="flex justify-between items-center">
                            <span class="text-sm text-gray-400 font-medium">Delivery</span>
                            <span class="text-sm font-bold text-black">₱15.00</span>
                        </div>
                        <div class="flex justify-between items-center pt-3 border-t border-dashed border-gray-200">
                            <span class="text-lg font-bold text-black">Total</span>
                            <span class="text-2xl font-black text-[#C0422A]">₱{{ number_format($subtotal + 15) }}</span>
                        </div>
                    </div>

                    <template x-if="step === 1">
                        <button type="button" @click="step = 2" class="w-full bg-black text-white py-5 rounded-2xl text-sm font-bold shadow-xl shadow-black/10 hover:bg-[#C0422A] transition-all">
                            Proceed to Payment
                        </button>
                    </template>
                    <template x-if="step === 2">
                        <div class="flex gap-4">
                            <button type="button" @click="step = 1" class="w-1/4 border-2 border-gray-100 py-5 rounded-2xl flex items-center justify-center text-gray-400 hover:text-black">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path></svg>
                            </button>
                            <button type="submit" class="flex-1 bg-[#C0422A] text-white py-5 rounded-2xl text-sm font-bold shadow-xl shadow-[#C0422A]/10 hover:bg-[#A33622] transition-all">
                                Place Order
                            </button>
                        </div>
                    </template>
                </div>
            </div>
        </div>
    </form>

    <!-- Address Book Modal -->
    <div x-show="showAddressModal" class="fixed inset-0 z-[100] flex items-center justify-center p-4" x-cloak>
        <div class="absolute inset-0 bg-black/60 backdrop-blur-sm" @click="showAddressModal = false"></div>
        <div class="relative bg-white w-full max-w-lg rounded-[40px] shadow-2xl overflow-hidden" x-transition>
            <div class="p-8 border-b border-gray-50 flex justify-between items-center">
                <h3 class="text-xl font-bold">Select Address</h3>
                <button @click="showAddressModal = false" class="text-gray-400 hover:text-black">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                </button>
            </div>
            <div class="p-8 space-y-4 max-h-[60vh] overflow-y-auto custom-scrollbar">
                <template x-for="addr in addresses" :key="addr.id">
                    <div class="border-2 rounded-2xl p-4 cursor-pointer transition-all hover:border-black" :class="address.id === addr.id ? 'border-black bg-gray-50' : 'border-gray-50'" @click="selectAddress(addr)">
                        <div class="flex gap-4">
                            <div class="w-10 h-10 rounded-full bg-gray-100 flex items-center justify-center text-gray-400 shrink-0">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path></svg>
                            </div>
                            <div>
                                <div class="text-sm font-bold text-black" x-text="addr.recipientName"></div>
                                <div class="text-[10px] text-gray-400 font-bold mt-1" x-text="addr.phone"></div>
                                <p class="text-xs text-gray-500 mt-2" x-text="addr.houseNo + ' ' + addr.street + ', ' + addr.barangay + ', ' + addr.city"></p>
                            </div>
                        </div>
                    </div>
                </template>
                <a href="/profile#addresses" class="flex items-center justify-center gap-2 p-4 border-2 border-dashed border-gray-200 rounded-2xl text-sm font-bold text-gray-400 hover:text-black hover:border-black transition-all">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
                    Manage Addresses
                </a>
            </div>
        </div>
    </div>
</div>
@endsection
