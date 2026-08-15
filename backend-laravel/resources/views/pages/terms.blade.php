@extends('layouts.app')

@section('content')
<div class="max-w-4xl mx-auto py-12 px-4">
    <!-- Header -->
    <div class="text-center mb-12">
        <div class="inline-flex items-center gap-2 px-3 py-1 bg-[#C0420A]/10 text-[#C0420A] text-[10px] font-black uppercase tracking-widest rounded-full mb-3">
            Terms of Service
        </div>
        <h1 class="font-serif text-4xl font-bold text-gray-900 mb-4">Terms & Conditions</h1>
        <p class="text-gray-500 text-sm max-w-xl mx-auto">
            Last updated: August 2026
        </p>
    </div>

    <!-- Main Content Card -->
    <div class="bg-white rounded-3xl border border-gray-100 shadow-sm p-8 lg:p-12 space-y-8 text-sm text-gray-600 leading-relaxed">
        <div>
            <h2 class="text-lg font-bold text-gray-900 mb-2">1. Agreement to Terms</h2>
            <p>
                By accessing or using the LumBarong platform, you agree to be bound by these Terms & Conditions. LumBarong provides a digital marketplace connecting buyers with authentic Lumban artisans.
            </p>
        </div>

        <div>
            <h2 class="text-lg font-bold text-gray-900 mb-2">2. Orders & Custom Tailoring</h2>
            <p>
                Barong Tagalog garments crafted to custom size specifications are tailored to the measurements provided by the customer. Customers are encouraged to verify measurements carefully prior to placing orders.
            </p>
        </div>

        <div>
            <h2 class="text-lg font-bold text-gray-900 mb-2">3. Seller Responsibilities</h2>
            <p>
                Artisans and sellers on LumBarong agree to deliver genuine hand-crafted quality products matching listed product specifications.
            </p>
        </div>

        <div>
            <h2 class="text-lg font-bold text-gray-900 mb-2">4. Returns & Refunds</h2>
            <p>
                Damaged or incorrect items may be submitted for return or refund review in accordance with our return guidelines through the customer orders panel.
            </p>
        </div>
    </div>
</div>
@endsection
