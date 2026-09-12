<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="Cache-Control" content="no-cache, no-store, must-revalidate">
    <title>Application Verification &amp; Document Review | LumBarong</title>
    <!-- Favicon -->
    <link rel="icon" type="image/x-icon" href="{{ asset('favicon.ico') }}">
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=Playfair+Display:wght@700;800&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Inter', sans-serif; background: #F7F3EE; }
        .font-serif { font-family: 'Playfair Display', serif; }
        [x-cloak] { display: none !important; }
    </style>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.14.8/dist/cdn.min.js"></script>
</head>
<body class="min-h-screen text-gray-800 flex flex-col justify-between p-4 sm:p-8" x-data="{
    residencyPreview: '{{ $seller->residencyCertificate ? asset($seller->residencyCertificate) : '' }}',
    businessPreview: '{{ $seller->businessPermit ? asset($seller->businessPermit) : '' }}',
    birPreview: '{{ $seller->birDocument ? asset($seller->birDocument) : '' }}',
    isSubmitting: false,
    handleFileSelect(event, type) {
        const file = event.target.files[0];
        if (!file) return;
        if (file.type.startsWith('image/')) {
            const reader = new FileReader();
            reader.onload = (e) => {
                if (type === 'residency') this.residencyPreview = e.target.result;
                if (type === 'business') this.businessPreview = e.target.result;
                if (type === 'bir') this.birPreview = e.target.result;
            };
            reader.readAsDataURL(file);
        } else {
            if (type === 'residency') this.residencyPreview = 'pdf';
            if (type === 'business') this.businessPreview = 'pdf';
            if (type === 'bir') this.birPreview = 'pdf';
        }
    }
}">

    @php
        $isIneligible = ($seller->status === 'rejected' && $seller->rejection_type === 'ineligible');
        $isCorrectionRequired = ($seller->status === 'rejected' && $seller->rejection_type === 'document_correction');
        $rejectionReasonText = $seller->rejection_reason ?: ($seller->rejectionReason ?: ($latestAudit->reason ?? null));
    @endphp

    {{-- Top Bar --}}
    <header class="max-w-4xl w-full mx-auto flex items-center justify-between py-2 border-b border-[#E5DDD5] mb-6">
        <div class="flex items-center gap-3">
            <img src="{{ asset('images/logo-icon.png') }}" alt="LumBarong Logo" class="w-10 h-10 object-contain rounded-full shadow-sm">
            <div>
                <h1 class="font-serif font-black text-lg text-gray-900 leading-tight">LumBarong</h1>
                <p class="text-[9px] font-black uppercase tracking-[0.25em] text-[#C0422A]">Artisan Partner Portal</p>
            </div>
        </div>
        <form action="{{ route('logout') }}" method="POST">
            @csrf
            <button type="submit" class="px-4 py-2 rounded-xl bg-white hover:bg-gray-100 border border-gray-200 text-xs font-bold text-gray-600 hover:text-red-600 transition-all cursor-pointer shadow-xs flex items-center gap-1.5">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/></svg>
                <span>Log Out</span>
            </button>
        </form>
    </header>

    {{-- Main Container --}}
    <main class="max-w-4xl w-full mx-auto flex-1">
        
        {{-- Flash Alerts --}}
        @if(session('success'))
            <div class="mb-6 p-4 rounded-2xl bg-emerald-50 border border-emerald-200 text-emerald-900 text-xs font-semibold flex items-start gap-3 shadow-xs">
                <span class="text-base leading-none">✓</span>
                <div>
                    <p class="font-bold">{{ session('success') }}</p>
                    <p class="text-[11px] text-emerald-700 mt-0.5">Our administration team will review your updated files shortly.</p>
                </div>
            </div>
        @endif

        @if(session('error'))
            <div class="mb-6 p-4 rounded-2xl bg-red-50 border border-red-200 text-red-900 text-xs font-semibold flex items-start gap-3 shadow-xs">
                <span class="text-base leading-none">⚠️</span>
                <p>{{ session('error') }}</p>
            </div>
        @endif

        @if($errors->any())
            <div class="mb-6 p-4 rounded-2xl bg-red-50 border border-red-200 text-red-900 text-xs shadow-xs space-y-1">
                <span class="font-bold block">Please resolve the following before submitting:</span>
                <ul class="list-disc list-inside space-y-0.5 text-[11px]">
                    @foreach($errors->all() as $err)
                        <li>{{ $err }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        {{-- Status Header Card --}}
        <div class="bg-white rounded-3xl border border-[#E5DDD5] p-6 sm:p-8 shadow-[0_10px_30px_rgba(60,40,20,0.04)] mb-6">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 pb-6 border-b border-gray-100">
                <div class="flex items-center gap-4">
                    <div class="w-14 h-14 rounded-2xl bg-[#F7F3EE] border border-[#E5DDD5] flex items-center justify-center font-black text-xl text-[#C0422A] shrink-0 shadow-inner">
                        {{ strtoupper(substr($seller->name, 0, 1)) }}
                    </div>
                    <div>
                        <div class="flex items-center gap-2 flex-wrap">
                            <h2 class="text-lg sm:text-xl font-black text-gray-900">{{ $seller->shopName ?? ($seller->name . "'s Workshop") }}</h2>
                            @if($isIneligible)
                                <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-[10px] font-black uppercase tracking-wider bg-rose-50 text-rose-800 border border-rose-200">
                                    <span class="w-1.5 h-1.5 rounded-full bg-rose-600"></span>
                                    Application Ineligible
                                </span>
                            @elseif($isCorrectionRequired)
                                <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-[10px] font-black uppercase tracking-wider bg-amber-50 text-amber-800 border border-amber-200">
                                    <span class="w-1.5 h-1.5 rounded-full bg-amber-500 animate-pulse"></span>
                                    Correction Required
                                </span>
                            @else
                                <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-[10px] font-black uppercase tracking-wider bg-amber-50 text-amber-800 border border-amber-200">
                                    <span class="w-1.5 h-1.5 rounded-full bg-amber-500 animate-pulse"></span>
                                    Pending Verification
                                </span>
                            @endif
                        </div>
                        <p class="text-xs text-gray-500 mt-0.5 font-medium">Artisan Applicant: <strong class="text-gray-800">{{ $seller->name }}</strong> · {{ $seller->email }}</p>
                    </div>
                </div>
            </div>

            {{-- Notice Box --}}
            @if($isIneligible)
                <div class="mt-6 p-5 rounded-2xl bg-rose-50/80 border border-rose-200/90 text-rose-950 text-xs space-y-3">
                    <div class="flex items-center gap-2 text-rose-800 font-bold text-xs uppercase tracking-wider">
                        <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                        <span>Eligibility Decision: Non-Qualified Application</span>
                    </div>
                    <p class="text-rose-900 leading-relaxed font-medium">
                        Thank you for your interest in LumBarong. After administrative review, we regret to inform you that your application does not meet the platform's core eligibility criteria.
                    </p>
                    @if($rejectionReasonText)
                        <div class="p-3.5 bg-white/95 border border-rose-200 rounded-xl text-rose-950 text-[11px] font-medium leading-relaxed shadow-xs">
                            <span class="text-rose-700 font-bold block text-[9px] uppercase tracking-widest mb-0.5">Administrative Reason:</span>
                            {{ $rejectionReasonText }}
                        </div>
                    @endif
                    <div class="p-3 bg-rose-100/60 rounded-xl text-[11px] text-rose-800 font-medium">
                        <strong>Important:</strong> LumBarong is strictly exclusive to authentic local artisans and registered heritage workshops located in Lumban, Laguna. Document resubmission is unavailable for ineligible applications.
                    </div>
                </div>
            @else
                <div class="mt-6 p-4.5 rounded-2xl bg-amber-50/70 border border-amber-200/90 text-amber-950 text-xs space-y-2">
                    <div class="flex items-center gap-2 text-amber-800 font-bold text-xs uppercase tracking-wider">
                        <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        <span>Action Required: Verification Documents</span>
                    </div>
                    <p class="text-amber-900 leading-relaxed font-medium">
                        Your artisan seller application requires document review or updates. Please review the notes below and attach clear, valid files so our administrators can verify and approve your shop.
                    </p>
                    @if($rejectionReasonText)
                        <div class="p-3 bg-white/90 border border-amber-200 rounded-xl text-amber-950 text-[11px] font-medium leading-relaxed">
                            <span class="text-gray-400 font-bold block text-[9px] uppercase tracking-wider mb-0.5">Admin Note / Feedback:</span>
                            {{ $rejectionReasonText }}
                        </div>
                    @endif
                </div>
            @endif
        </div>

        @if($isIneligible)
            {{-- Ineligible Action Box (No Upload Form) --}}
            <div class="bg-white rounded-3xl border border-[#E5DDD5] p-6 sm:p-8 shadow-[0_10px_30px_rgba(60,40,20,0.04)] space-y-4 text-center">
                <div class="w-12 h-12 bg-rose-50 text-rose-600 rounded-2xl flex items-center justify-center mx-auto mb-2">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"/></svg>
                </div>
                <h3 class="text-base font-bold text-gray-900">Application Closed</h3>
                <p class="text-xs text-gray-500 max-w-lg mx-auto leading-relaxed">
                    If you believe this determination was made in error, or if your artisan business is genuinely based in Lumban, Laguna, please contact our support team.
                </p>
                <div class="pt-4 flex flex-col sm:flex-row items-center justify-center gap-3">
                    <a href="mailto:lumbarongsupport@gmail.com" class="w-full sm:w-auto px-6 py-2.5 bg-[#3D2B1F] hover:bg-[#C0422A] text-white rounded-xl text-xs font-bold transition-all shadow-xs text-center">
                        Contact Support: lumbarongsupport@gmail.com
                    </a>
                    <form action="{{ route('logout') }}" method="POST" class="w-full sm:w-auto">
                        @csrf
                        <button type="submit" class="w-full px-6 py-2.5 bg-gray-100 hover:bg-gray-200 text-gray-700 rounded-xl text-xs font-bold transition-all">
                            Log Out
                        </button>
                    </form>
                </div>
            </div>
        @else
            {{-- Document Re-upload Form --}}
            <form action="{{ route('seller.verification-pending.upload') }}" method="POST" enctype="multipart/form-data" @submit="isSubmitting = true" class="space-y-6">
                @csrf

                <div class="bg-white rounded-3xl border border-[#E5DDD5] p-6 sm:p-8 shadow-[0_10px_30px_rgba(60,40,20,0.04)] space-y-6">
                    <div>
                        <h3 class="text-sm font-black text-gray-900 uppercase tracking-wider">Submitted Credentials &amp; Certifications</h3>
                        <p class="text-xs text-gray-500 mt-0.5">Upload clear PDF files or high-resolution images (JPG, PNG, WEBP max 20MB).</p>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-3 gap-5">
                        
                        {{-- 1. Barangay Residency Certificate --}}
                        <div class="p-5 rounded-2xl bg-[#F9F6F2] border border-[#E5DDD5] flex flex-col justify-between space-y-4 hover:border-gray-400 transition-colors">
                            <div class="space-y-2">
                                <div class="flex items-center justify-between">
                                    <span class="text-[10px] font-black uppercase tracking-wider text-[#C0422A]">Required *</span>
                                    <span class="text-[10px] font-bold text-gray-400">Step 1</span>
                                </div>
                                <h4 class="text-xs font-bold text-gray-900 leading-snug">Barangay Residency Certificate</h4>
                                <p class="text-[11px] text-gray-500 leading-relaxed">Proves Lumban / local artisan residency &amp; workshop authenticity.</p>
                            </div>

                            {{-- Preview Box --}}
                            <div class="w-full h-32 rounded-xl bg-white border border-dashed border-gray-300 flex items-center justify-center overflow-hidden relative group">
                                <template x-if="residencyPreview && residencyPreview !== 'pdf'">
                                    <img :src="residencyPreview" class="w-full h-full object-cover">
                                </template>
                                <template x-if="residencyPreview === 'pdf'">
                                    <div class="text-center p-2 text-gray-600">
                                        <svg class="w-8 h-8 mx-auto text-red-500 mb-1" fill="currentColor" viewBox="0 0 20 20"><path d="M4 4a2 2 0 012-2h4.586A2 2 0 0112 2.586L15.414 6A2 2 0 0116 7.414V16a2 2 0 01-2 2H6a2 2 0 01-2-2V4z"/></svg>
                                        <span class="text-[10px] font-bold uppercase tracking-wider">PDF Document Attached</span>
                                    </div>
                                </template>
                                <template x-if="!residencyPreview">
                                    <div class="text-center p-2 text-gray-400">
                                        <svg class="w-8 h-8 mx-auto text-gray-300 mb-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"/></svg>
                                        <span class="text-[10px] font-semibold">No file attached</span>
                                    </div>
                                </template>
                            </div>

                            <div>
                                <label class="block w-full py-2 px-3 bg-white hover:bg-gray-100 border border-gray-300 rounded-xl text-center text-xs font-bold text-gray-700 cursor-pointer transition-all shadow-xs">
                                    <span>Choose File</span>
                                    <input type="file" name="residencyCertificate" accept=".pdf,image/*" @change="handleFileSelect($event, 'residency')" class="hidden">
                                </label>
                            </div>
                        </div>

                        {{-- 2. Business Permit --}}
                        <div class="p-5 rounded-2xl bg-[#F9F6F2] border border-[#E5DDD5] flex flex-col justify-between space-y-4 hover:border-gray-400 transition-colors">
                            <div class="space-y-2">
                                <div class="flex items-center justify-between">
                                    <span class="text-[10px] font-black uppercase tracking-wider text-gray-400">Optional / If Applicable</span>
                                    <span class="text-[10px] font-bold text-gray-400">Step 2</span>
                                </div>
                                <h4 class="text-xs font-bold text-gray-900 leading-snug">Mayor's / Business Permit</h4>
                                <p class="text-[11px] text-gray-500 leading-relaxed">Registered business permit for commercial workshops.</p>
                            </div>

                            {{-- Preview Box --}}
                            <div class="w-full h-32 rounded-xl bg-white border border-dashed border-gray-300 flex items-center justify-center overflow-hidden relative group">
                                <template x-if="businessPreview && businessPreview !== 'pdf'">
                                    <img :src="businessPreview" class="w-full h-full object-cover">
                                </template>
                                <template x-if="businessPreview === 'pdf'">
                                    <div class="text-center p-2 text-gray-600">
                                        <svg class="w-8 h-8 mx-auto text-red-500 mb-1" fill="currentColor" viewBox="0 0 20 20"><path d="M4 4a2 2 0 012-2h4.586A2 2 0 0112 2.586L15.414 6A2 2 0 0116 7.414V16a2 2 0 01-2 2H6a2 2 0 01-2-2V4z"/></svg>
                                        <span class="text-[10px] font-bold uppercase tracking-wider">PDF Document Attached</span>
                                    </div>
                                </template>
                                <template x-if="!businessPreview">
                                    <div class="text-center p-2 text-gray-400">
                                        <svg class="w-8 h-8 mx-auto text-gray-300 mb-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"/></svg>
                                        <span class="text-[10px] font-semibold">No file attached</span>
                                    </div>
                                </template>
                            </div>

                            <div>
                                <label class="block w-full py-2 px-3 bg-white hover:bg-gray-100 border border-gray-300 rounded-xl text-center text-xs font-bold text-gray-700 cursor-pointer transition-all shadow-xs">
                                    <span>Choose File</span>
                                    <input type="file" name="businessPermit" accept=".pdf,image/*" @change="handleFileSelect($event, 'business')" class="hidden">
                                </label>
                            </div>
                        </div>

                        {{-- 3. BIR Tax Registration --}}
                        <div class="p-5 rounded-2xl bg-[#F9F6F2] border border-[#E5DDD5] flex flex-col justify-between space-y-4 hover:border-gray-400 transition-colors">
                            <div class="space-y-2">
                                <div class="flex items-center justify-between">
                                    <span class="text-[10px] font-black uppercase tracking-wider text-gray-400">Optional / If Applicable</span>
                                    <span class="text-[10px] font-bold text-gray-400">Step 3</span>
                                </div>
                                <h4 class="text-xs font-bold text-gray-900 leading-snug">BIR / Tax Registration (2303)</h4>
                                <p class="text-[11px] text-gray-500 leading-relaxed">Tax registration form for registered enterprises.</p>
                            </div>

                            {{-- Preview Box --}}
                            <div class="w-full h-32 rounded-xl bg-white border border-dashed border-gray-300 flex items-center justify-center overflow-hidden relative group">
                                <template x-if="birPreview && birPreview !== 'pdf'">
                                    <img :src="birPreview" class="w-full h-full object-cover">
                                </template>
                                <template x-if="birPreview === 'pdf'">
                                    <div class="text-center p-2 text-gray-600">
                                        <svg class="w-8 h-8 mx-auto text-red-500 mb-1" fill="currentColor" viewBox="0 0 20 20"><path d="M4 4a2 2 0 012-2h4.586A2 2 0 0112 2.586L15.414 6A2 2 0 0116 7.414V16a2 2 0 01-2 2H6a2 2 0 01-2-2V4z"/></svg>
                                        <span class="text-[10px] font-bold uppercase tracking-wider">PDF Document Attached</span>
                                    </div>
                                </template>
                                <template x-if="!birPreview">
                                    <div class="text-center p-2 text-gray-400">
                                        <svg class="w-8 h-8 mx-auto text-gray-300 mb-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"/></svg>
                                        <span class="text-[10px] font-semibold">No file attached</span>
                                    </div>
                                </template>
                            </div>

                            <div>
                                <label class="block w-full py-2 px-3 bg-white hover:bg-gray-100 border border-gray-300 rounded-xl text-center text-xs font-bold text-gray-700 cursor-pointer transition-all shadow-xs">
                                    <span>Choose File</span>
                                    <input type="file" name="birDocument" accept=".pdf,image/*" @change="handleFileSelect($event, 'bir')" class="hidden">
                                </label>
                            </div>
                        </div>

                    </div>

                    {{-- Action Bar --}}
                    <div class="pt-4 border-t border-gray-100 flex flex-col sm:flex-row items-center justify-between gap-4">
                        <p class="text-[11px] text-gray-400">
                            Need assistance? Contact our artisan support desk at <a href="mailto:lumbarongsupport@gmail.com" class="text-[#C0422A] underline font-bold">lumbarongsupport@gmail.com</a>.
                        </p>
                        <button type="submit" :disabled="isSubmitting" class="w-full sm:w-auto px-8 py-3 bg-[#C0422A] hover:bg-[#a83720] text-white rounded-2xl text-xs font-black uppercase tracking-wider transition-all shadow-md hover:shadow-lg disabled:opacity-50 cursor-pointer flex items-center justify-center gap-2">
                            <svg x-show="isSubmitting" class="animate-spin w-4 h-4 text-white" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                            <span x-text="isSubmitting ? 'Submitting Files...' : 'Submit Documents for Review'"></span>
                        </button>
                    </div>
                </div>
            </form>
        @endif

    </main>

    {{-- Footer --}}
    <footer class="max-w-4xl w-full mx-auto text-center py-6 text-xs text-gray-400">
        &copy; {{ date('Y') }} LumBarong Artisan Registry &amp; Marketplace. All rights reserved.
    </footer>

</body>
</html>
