@extends('layouts.app')

@section('content')
<div class="max-w-4xl mx-auto py-12 px-4">
    <!-- Header -->
    <div class="text-center mb-12">
        <div class="inline-flex items-center gap-2 px-3 py-1 bg-[#C0420A]/10 text-[#C0420A] text-[10px] font-black uppercase tracking-widest rounded-full mb-3">
            Legal & Data Safety
        </div>
        <h1 class="font-serif text-4xl font-bold text-gray-900 mb-4">Privacy Policy</h1>
        <p class="text-gray-500 text-sm max-w-xl mx-auto">
            Last updated: August 2026
        </p>
    </div>

    <!-- Main Content Card -->
    <div class="bg-white rounded-3xl border border-gray-100 shadow-sm p-8 lg:p-12 space-y-8 text-sm text-gray-600 leading-relaxed">
        <div>
            <h2 class="text-lg font-bold text-gray-900 mb-2">1. Information We Collect</h2>
            <p>
                When you use LumBarong, we collect personal information necessary to process your orders and provide a seamless marketplace experience, including your name, email address, shipping address, phone number, and custom clothing measurements.
            </p>
        </div>

        <div>
            <h2 class="text-lg font-bold text-gray-900 mb-2">2. How We Use Your Information</h2>
            <p>
                We use your data to process orders, communicate order updates, facilitate custom tailoring with sellers, protect against fraudulent activity, and improve platform performance.
            </p>
        </div>

        <div>
            <h2 class="text-lg font-bold text-gray-900 mb-2">3. Payment & Data Protection</h2>
            <p>
                Payments are processed securely via verified payment options. LumBarong does not store raw banking PINs or credit card credentials.
            </p>
        </div>

        <div>
            <h2 class="text-lg font-bold text-gray-900 mb-2">4. Your Rights</h2>
            <p>
                You can update or delete your profile information, manage your saved shipping addresses, or contact support to request account updates at any time.
            </p>
        </div>
    </div>
</div>
@endsection
