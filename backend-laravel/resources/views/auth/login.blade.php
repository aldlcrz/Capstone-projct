<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login | LumBarong</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=Playfair+Display:wght@700;800&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Inter', sans-serif; background: #F7F3EE; }
        .font-serif { font-family: 'Playfair Display', serif; }
        .no-scrollbar::-webkit-scrollbar { display: none; }
        .no-scrollbar { -ms-overflow-style: none; scrollbar-width: none; }
        [x-cloak] { display: none !important; }
    </style>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
</head>
<body class="min-h-screen flex items-center justify-center p-6 relative overflow-hidden">
    <!-- Subtle warm blobs -->
    <div class="absolute top-0 right-0 w-140 h-140 rounded-full -translate-y-1/2 translate-x-1/3 blur-3xl opacity-[0.04] pointer-events-none bg-[#C0422A]"></div>
    <div class="absolute bottom-0 left-0 w-95 h-95 rounded-full translate-y-1/2 -translate-x-1/3 blur-3xl opacity-[0.12] pointer-events-none bg-[#D4B896]"></div>

    <div class="w-full max-w-md bg-white rounded-[2.5rem] border border-[#E5DDD5] p-8 shadow-[0_20px_60px_rgba(60,40,20,0.08)] relative z-10">
        <div class="relative mb-10 text-center">
            <a href="/" class="absolute left-0 top-1/2 -translate-y-1/2 w-10 h-10 rounded-full bg-[#F9F6F2] flex items-center justify-center hover:bg-gray-100 text-gray-600 hover:text-[#C0422A] transition-all shadow-sm" title="Back to Home">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                </svg>
            </a>
            <h1 class="font-serif text-2xl font-black italic tracking-tight text-[#C0422A] mb-1">LumBarong</h1>
            <p class="text-[11px] font-bold uppercase tracking-[0.3em] text-gray-400">Authentication Portal</p>
        </div>

        <form action="/login" method="POST" class="space-y-6">
            @csrf
            <div class="space-y-2">
                <label class="text-[10px] font-bold uppercase tracking-widest px-5 block text-gray-500">Email Address</label>
                <input 
                    type="email" 
                    name="email" 
                    value="{{ old('email') }}"
                    required 
                    class="w-full h-14 bg-[#F9F6F2] rounded-full px-8 text-sm font-medium border-2 border-transparent focus:border-[#C0422A] focus:bg-white outline-none transition-all"
                >
            </div>

            <div class="space-y-2" x-data="{ show: false }">
                <div class="flex justify-between items-center px-5">
                    <label class="text-[10px] font-bold uppercase tracking-widest text-gray-500">Password</label>
                    <a href="/forgot-password" class="text-[10px] font-bold uppercase tracking-widest text-[#C0422A]">Forgot?</a>
                </div>
                <div class="relative">
                    <input 
                        :type="show ? 'text' : 'password'"
                        name="password" 
                        required 
                        class="w-full h-14 bg-[#F9F6F2] rounded-full px-8 pr-14 text-sm font-medium border-2 border-transparent focus:border-[#C0422A] focus:bg-white outline-none transition-all"
                    >
                    <button type="button" @click="show = !show"
                        class="absolute right-5 top-1/2 -translate-y-1/2 text-gray-400 hover:text-[#C0422A] transition-colors">
                        <!-- Eye open -->
                        <svg x-show="!show" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                        </svg>
                        <!-- Eye off -->
                        <svg x-show="show" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" x-cloak>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M13.875 18.825A10.05 10.05 0 0112 19c-4.477 0-8.268-2.943-9.542-7a9.97 9.97 0 012.458-4.017M6.53 6.53A9.97 9.97 0 0112 5c4.478 0 8.268 2.943 9.542 7a10.05 10.05 0 01-4.132 5.411M15 12a3 3 0 00-3-3m0 0a3 3 0 00-3 3m3-3L3 3m18 18L9 9"/>
                        </svg>
                    </button>
                </div>
            </div>

            <button type="submit" class="w-full h-14 bg-[#3D2B1F] text-white rounded-full font-bold uppercase tracking-[0.2em] text-[10px] shadow-xl shadow-black/10 hover:bg-[#C0422A] transition-all">
                Log-In
            </button>

            <div class="pt-4">
                <div class="flex items-center gap-4 mb-6">
                    <div class="h-px flex-1 bg-[#E5DDD5]"></div>
                    <span class="text-[9px] font-bold text-[#8C7B70] uppercase tracking-widest">social gateway</span>
                    <div class="h-px flex-1 bg-[#E5DDD5]"></div>
                </div>
                <div class="flex justify-center">
                    <div id="g_id_onload"
                        data-client_id="{{ config('services.google.client_id') }}"
                        data-context="signin"
                        data-ux_mode="popup"
                        data-callback="handleCredentialResponse"
                        data-auto_prompt="false">
                    </div>
                    <div class="g_id_signin"
                        data-type="standard"
                        data-shape="pill"
                        data-theme="outline"
                        data-text="signin_with"
                        data-size="large"
                        data-logo_alignment="left">
                    </div>
                </div>
            </div>
        </form>

        <script src="https://accounts.google.com/gsi/client" async defer></script>
        <script>
            function handleCredentialResponse(response) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.action = '/auth/google';
                
                const csrfToken = document.createElement('input');
                csrfToken.type = 'hidden';
                csrfToken.name = '_token';
                csrfToken.value = '{{ csrf_token() }}';
                form.appendChild(csrfToken);

                const credentialInput = document.createElement('input');
                credentialInput.type = 'hidden';
                credentialInput.name = 'credential';
                credentialInput.value = response.credential;
                form.appendChild(credentialInput);

                document.body.appendChild(form);
                form.submit();
            }
        </script>

        <div class="mt-10 text-center">
            <p class="text-[10px] font-bold uppercase tracking-widest text-gray-400">
                Don't have an account? 
                <a href="/register" class="text-[#C0422A] ml-1">Create Account</a>
            </p>
        </div>
    </div>

    <!-- ==================== ACCOUNT FROZEN / LOGIN ERROR MODALS ==================== -->
    @php
        $gcashNumber = \App\Models\SystemSetting::where('key', 'superadmin_gcash_number')->value('value') ?? '';
        $gcashQr     = \App\Models\SystemSetting::where('key', 'superadmin_gcash_qr')->value('value') ?? '';
        $mayaNumber  = \App\Models\SystemSetting::where('key', 'superadmin_maya_number')->value('value') ?? '';
        $mayaQr      = \App\Models\SystemSetting::where('key', 'superadmin_maya_qr')->value('value') ?? '';
        $errMsg      = $errors->first();
        $isFrozenErr = $errors->any() && (
            str_contains(strtolower($errMsg), 'commission') || 
            str_contains(strtolower($errMsg), 'frozen')
        );
    @endphp

    @if(session('payment_submitted'))
    <!-- Payment Submitted Success Modal -->
    <div x-data="{ show: true }" x-show="show"
         class="fixed inset-0 z-[99999] flex items-center justify-center p-4 bg-black/60 backdrop-blur-md"
         style="display: none;" x-cloak>
        <div @click.away="show = false" class="w-full max-w-md bg-white rounded-4xl p-6 lg:p-8 shadow-2xl border border-gray-100 text-center space-y-4">
            <div class="w-14 h-14 rounded-2xl bg-green-50 text-green-600 border border-green-200 flex items-center justify-center mx-auto shadow-sm">
                <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"></path></svg>
            </div>
            <h3 class="font-serif text-xl font-bold text-gray-900">Payment Submitted!</h3>
            <p class="text-xs text-gray-600 leading-relaxed">{{ session('payment_submitted') }}</p>
            <button type="button" @click="show = false" class="w-full py-3 bg-[#3D2B1F] text-white rounded-full font-bold uppercase tracking-widest text-[10px] hover:bg-[#C0422A] transition-all">
                Close
            </button>
        </div>
    </div>
    @endif

    @if($errors->any())
    <div 
        x-data="{ show: true, activeTab: 'gcash' }"
        x-show="show"
        x-transition:enter="transition ease-out duration-300"
        x-transition:enter-start="opacity-0 scale-95"
        x-transition:enter-end="opacity-100 scale-100"
        x-transition:leave="transition ease-in duration-200"
        x-transition:leave-start="opacity-100 scale-100"
        x-transition:leave-end="opacity-0 scale-95"
        class="fixed inset-0 z-[9999] flex items-center justify-center p-4 bg-black/60 backdrop-blur-md overflow-y-auto"
        style="display: none;"
        x-cloak
        @keydown.escape.window="show = false"
    >
        @if($isFrozenErr)
        <!-- Account Frozen Payment Modal -->
        <div @click.away="show = false" class="w-full max-w-lg bg-white rounded-[2.5rem] p-6 lg:p-8 shadow-2xl border border-gray-100 text-center relative space-y-6 max-h-[90vh] overflow-y-auto no-scrollbar my-auto">
            <button @click="show = false" class="absolute top-5 right-5 text-gray-400 hover:text-gray-600 transition-colors">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
            </button>

            <!-- Icon & Header -->
            <div class="space-y-2 pt-2">
                <div class="w-14 h-14 rounded-2xl bg-red-50 text-red-600 border border-red-100 flex items-center justify-center mx-auto shadow-sm">
                    <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                    </svg>
                </div>
                <div>
                    <span class="inline-block px-3 py-1 bg-red-50 text-red-600 border border-red-100 rounded-full text-[10px] font-black uppercase tracking-widest mb-1">Account Suspended</span>
                    <h3 class="font-serif text-2xl font-bold text-gray-900">Pay Commission to Continue</h3>
                    <p class="text-xs text-gray-500 max-w-sm mx-auto mt-1 leading-relaxed">
                        Your shop account has been frozen due to overdue commission fees. Please send your payment and submit your reference details below to unfreeze your account.
                    </p>
                </div>
            </div>

            <!-- Payment Method Selector Tabs -->
            <div class="bg-[#F9F6F2] p-1.5 rounded-2xl flex gap-2 border border-[#E5DDD5]">
                <button type="button" @click="activeTab = 'gcash'" 
                        :class="activeTab === 'gcash' ? 'bg-white text-blue-600 shadow-sm border border-blue-100 font-black' : 'text-gray-500 font-bold hover:text-gray-800'"
                        class="flex-1 py-2.5 rounded-xl text-xs uppercase tracking-wider transition-all flex items-center justify-center gap-2">
                    <span class="w-2.5 h-2.5 rounded-full bg-blue-500"></span> GCash
                </button>
                <button type="button" @click="activeTab = 'maya'" 
                        :class="activeTab === 'maya' ? 'bg-white text-emerald-600 shadow-sm border border-emerald-100 font-black' : 'text-gray-500 font-bold hover:text-gray-800'"
                        class="flex-1 py-2.5 rounded-xl text-xs uppercase tracking-wider transition-all flex items-center justify-center gap-2">
                    <span class="w-2.5 h-2.5 rounded-full bg-emerald-500"></span> Maya
                </button>
            </div>

            <!-- GCash Details Container -->
            <div x-show="activeTab === 'gcash'" class="space-y-4" x-transition:enter="transition ease-out duration-200">
                @if($gcashQr)
                    <div class="bg-gray-50 border border-gray-200 rounded-2xl p-4 flex flex-col items-center">
                        <img src="{{ asset('storage/' . $gcashQr) }}" class="w-44 h-44 object-contain rounded-xl border border-gray-200 bg-white p-2 shadow-sm" alt="GCash QR Code">
                        <span class="text-[10px] font-bold text-gray-400 uppercase tracking-widest mt-2">Scan QR Code via GCash App</span>
                    </div>
                @endif

                @if($gcashNumber)
                    <div x-data="{ copied: false }" class="bg-blue-50/70 border border-blue-100 rounded-2xl p-4 flex items-center justify-between">
                        <div class="text-left">
                            <span class="text-[10px] font-bold text-blue-600 uppercase tracking-widest block">GCash Account Number</span>
                            <span class="text-base font-black text-gray-900 font-mono tracking-wider">{{ $gcashNumber }}</span>
                        </div>
                        <button type="button" 
                                @click="navigator.clipboard.writeText('{{ $gcashNumber }}'); copied = true; setTimeout(() => copied = false, 2500)"
                                class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-xl text-xs font-bold uppercase tracking-wider transition-all shadow-sm flex items-center gap-1.5">
                            <span x-text="copied ? 'Copied! ✓' : 'Copy Number'">Copy Number</span>
                        </button>
                    </div>
                @elseif(!$gcashQr)
                    <div class="p-4 rounded-2xl bg-gray-50 border border-dashed border-gray-200 text-xs text-gray-400 font-medium">
                        GCash payment info has not been configured yet.
                    </div>
                @endif
            </div>

            <!-- Maya Details Container -->
            <div x-show="activeTab === 'maya'" class="space-y-4" x-transition:enter="transition ease-out duration-200" style="display: none;">
                @if($mayaQr)
                    <div class="bg-gray-50 border border-gray-200 rounded-2xl p-4 flex flex-col items-center">
                        <img src="{{ asset('storage/' . $mayaQr) }}" class="w-44 h-44 object-contain rounded-xl border border-gray-200 bg-white p-2 shadow-sm" alt="Maya QR Code">
                        <span class="text-[10px] font-bold text-gray-400 uppercase tracking-widest mt-2">Scan QR Code via Maya App</span>
                    </div>
                @endif

                @if($mayaNumber)
                    <div x-data="{ copied: false }" class="bg-emerald-50/70 border border-emerald-100 rounded-2xl p-4 flex items-center justify-between">
                        <div class="text-left">
                            <span class="text-[10px] font-bold text-emerald-600 uppercase tracking-widest block">Maya Account Number</span>
                            <span class="text-base font-black text-gray-900 font-mono tracking-wider">{{ $mayaNumber }}</span>
                        </div>
                        <button type="button" 
                                @click="navigator.clipboard.writeText('{{ $mayaNumber }}'); copied = true; setTimeout(() => copied = false, 2500)"
                                class="px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white rounded-xl text-xs font-bold uppercase tracking-wider transition-all shadow-sm flex items-center gap-1.5">
                            <span x-text="copied ? 'Copied! ✓' : 'Copy Number'">Copy Number</span>
                        </button>
                    </div>
                @elseif(!$mayaQr)
                    <div class="p-4 rounded-2xl bg-gray-50 border border-dashed border-gray-200 text-xs text-gray-400 font-medium">
                        Maya payment info has not been configured yet.
                    </div>
                @endif
            </div>

            <!-- Submit Payment Proof Form -->
            <form action="{{ route('commission.submit-payment') }}" method="POST" enctype="multipart/form-data" class="bg-[#F9F6F2] border border-[#E5DDD5] rounded-2xl p-5 text-left space-y-4">
                @csrf
                <input type="hidden" name="email" value="{{ old('email') }}">

                <div class="flex items-center justify-between border-b border-[#E5DDD5] pb-3">
                    <h4 class="text-xs font-black text-[#3D2B1F] uppercase tracking-wider">Submit Payment Verification</h4>
                    <span class="text-[10px] text-gray-400 font-bold uppercase">Required</span>
                </div>

                <div class="space-y-1.5">
                    <label class="block text-[10px] font-bold text-gray-500 uppercase tracking-widest">Payment Method Used</label>
                    <select name="payment_method" x-model="activeTab" class="w-full px-4 py-2.5 bg-white border border-[#E5DDD5] rounded-xl text-xs font-bold text-[#3D2B1F] outline-none">
                        <option value="gcash">GCash</option>
                        <option value="maya">Maya</option>
                    </select>
                </div>

                <div class="space-y-1.5">
                    <label class="block text-[10px] font-bold text-gray-500 uppercase tracking-widest">Reference / Transaction Number</label>
                    <input type="text" name="reference_number" required placeholder="e.g. 100234567891"
                           class="w-full px-4 py-2.5 bg-white border border-[#E5DDD5] rounded-xl text-xs font-bold text-[#3D2B1F] focus:outline-none focus:border-[#C0422A]">
                </div>

                <div class="space-y-1.5">
                    <label class="block text-[10px] font-bold text-gray-500 uppercase tracking-widest">Upload Screenshot Proof</label>
                    <input type="file" name="proof_image" accept="image/*" required
                           class="w-full text-xs text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-xl file:border-0 file:text-xs file:font-bold file:bg-[#3D2B1F] file:text-white hover:file:bg-[#C0422A]">
                </div>

                <button type="submit" class="w-full py-3.5 bg-[#C0422A] text-white rounded-xl font-bold uppercase tracking-[0.2em] text-[10px] hover:bg-[#a83808] transition-all shadow-md mt-2">
                    Submit Payment Proof →
                </button>
            </form>
        </div>

        @else
        <!-- Standard Login Failed Modal -->
        <div @click.away="show = false" class="w-full max-w-sm bg-white rounded-3xl p-6 shadow-2xl border border-gray-100 text-center relative space-y-4 my-auto">
            <button @click="show = false" class="absolute top-4 right-4 text-gray-400 hover:text-gray-600 transition-colors">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
            </button>

            <div class="w-12 h-12 rounded-full bg-red-50 text-red-600 border border-red-100 flex items-center justify-center mx-auto shadow-sm">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12"></path></svg>
            </div>

            <div>
                <h3 class="text-sm font-black text-black uppercase tracking-wider">Login Failed</h3>
                <p class="text-xs text-gray-500 font-medium mt-1 leading-relaxed">{{ $errMsg }}</p>
            </div>

            <button @click="show = false" class="w-full py-3 bg-[#3D2B1F] text-white rounded-full font-bold uppercase tracking-widest text-[10px] hover:bg-[#C0422A] transition-all">
                Okay
            </button>
        </div>
        @endif
    </div>
    @endif
</body>
</html>