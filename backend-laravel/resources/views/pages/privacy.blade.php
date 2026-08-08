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
            <h2 class="text-lg font-bold text-gray-900 mb-2">1. Compliance with Data Privacy Act of 2012 (RA 10173)</h2>
            <p>
                LumBarong is committed to protecting your personal data in accordance with <strong>Republic Act No. 10173</strong>, also known as the <em>Data Privacy Act of 2012 (DPA)</em>, its Implementing Rules and Regulations (IRR), and issuances of the National Privacy Commission (NPC). As a user of LumBarong, your privacy rights as a Data Subject are strictly respected and safeguarded.
            </p>
        </div>

        <div>
            <h2 class="text-lg font-bold text-gray-900 mb-2">2. Information We Collect</h2>
            <p>
                To facilitate custom tailoring and secure transactions between buyers and Lumban artisans, we collect personal information including your full name, email address, contact numbers, delivery addresses, transaction payment proof, and custom body measurements (neck, chest, shoulder, sleeve, waist, full length). For seller registration, verification documents such as government IDs, business permits, and BIR certificates are securely gathered.
            </p>
        </div>

        <div>
            <h2 class="text-lg font-bold text-gray-900 mb-2">3. Purpose and Scope of Data Processing</h2>
            <p>
                Collected data is processed strictly for fulfilling orders, managing custom tailoring specifications, processing seller payouts, verifying accounts, preventing fraud, and delivering essential notifications. We do not sell or lease your personal information to third-party marketing brokers.
            </p>
        </div>

        <div>
            <h2 class="text-lg font-bold text-gray-900 mb-2">4. Data Subject Rights Under RA 10173</h2>
            <p>
                In accordance with the Data Privacy Act of 2012, you are entitled to the following rights:
            </p>
            <ul class="list-disc list-inside mt-2 space-y-1.5 pl-2 text-gray-600 font-medium">
                <li><strong>Right to be Informed:</strong> Know how your personal information is collected, processed, and stored.</li>
                <li><strong>Right to Access:</strong> Request reasonable access to your personal data held by LumBarong.</li>
                <li><strong>Right to Rectification:</strong> Request correction of inaccurate, outdated, or inaccurate information.</li>
                <li><strong>Right to Erasure or Blocking:</strong> Request deletion or suspension of your account and personal data.</li>
                <li><strong>Right to Data Portability:</strong> Obtain a copy of your personal data in a readable digital format.</li>
            </ul>
        </div>

        <div>
            <h2 class="text-lg font-bold text-gray-900 mb-2">5. Data Security & Storage Controls</h2>
            <p>
                We enforce organizational, physical, and technical security measures—including encrypted storage, secure HTTPS protocols, and role-based access control—to shield your personal information from unauthorized access, alteration, or disclosure.
            </p>
        </div>
    </div>
</div>
@endsection
