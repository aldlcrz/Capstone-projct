<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Seller Registration | LumBarong</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script defer src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=Playfair+Display:wght@700;800&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Inter', sans-serif; background: #F7F3EE; }
        .font-serif { font-family: 'Playfair Display', serif; }
        .no-scrollbar::-webkit-scrollbar { display: none; }
        .no-scrollbar { -ms-overflow-style: none; scrollbar-width: none; }
        
        [x-cloak] { display: none !important; }

        .step-indicator {
            width: 38px;
            height: 38px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
            font-size: 13px;
            font-weight: 700;
            transition: all 0.3s ease;
        }
        .step-active { background: #C0422A; color: white; box-shadow: 0 10px 25px rgba(192, 66, 42, 0.25); }
        .step-inactive { background: #F9F7F4; color: #E5E7EB; }
        
        .premium-input {
            width: 100%;
            height: 56px;
            background: #F9F6F2;
            border-radius: 9999px;
            padding: 0 32px;
            font-size: 14px;
            font-weight: 500;
            border: 2px solid transparent;
            outline: none;
            transition: all 0.3s ease;
        }
        .premium-input:focus { border-color: #C0422A; background: white; }
        
        .upload-card {
            background: #F9F6F2;
            border-radius: 24px;
            padding: 24px;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            border: 2px dashed #E5DDD5;
            transition: all 0.3s ease;
            cursor: pointer;
            height: 140px;
        }
        .upload-card:hover { border-color: #C0422A; background: #FDFBFA; }
    </style>
</head>
<body class="min-h-screen flex items-center justify-center p-6 relative overflow-hidden">
    <!-- Background Decor -->
    <div class="absolute top-0 right-0 w-[560px] h-[560px] rounded-full -translate-y-1/2 translate-x-1/3 blur-3xl opacity-[0.04] pointer-events-none bg-[#C0422A]"></div>
    <div class="absolute bottom-0 left-0 w-[380px] h-[380px] rounded-full translate-y-1/2 -translate-x-1/3 blur-3xl opacity-[0.12] pointer-events-none bg-[#D4B896]"></div>

    <div class="w-full max-w-xl bg-white rounded-[3rem] border border-[#E5DDD5] p-8 md:p-12 shadow-[0_20px_60px_rgba(60,40,20,0.08)] relative z-10 max-h-[95vh] overflow-y-auto no-scrollbar" 
         x-data="{ step: 1 }" x-cloak>
        
        <!-- Header -->
        <div class="relative mb-12 text-center">
            <!-- Back Button -->
            <button x-show="step > 1" @click="step--" class="absolute left-0 top-1/2 -translate-y-1/2 w-10 h-10 rounded-full bg-[#F9F6F2] flex items-center justify-center hover:bg-gray-100 transition-all">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-gray-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                </svg>
            </button>
            <button x-show="step === 1" @click="window.history.back()" class="absolute left-0 top-1/2 -translate-y-1/2 w-10 h-10 rounded-full bg-[#F9F6F2] flex items-center justify-center hover:bg-gray-100 transition-all">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-gray-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                </svg>
            </button>

            <h1 class="font-serif text-4xl font-black tracking-tight text-[#C0422A] mb-1">LumBarong</h1>
            <p class="text-[10px] font-bold uppercase tracking-[0.4em] text-[#9CA3AF]">Seller Registration</p>
        </div>

        <!-- Step Indicator -->
        <div class="flex items-center justify-center gap-2 mb-12">
            <div class="step-indicator" :class="step === 1 ? 'step-active' : 'step-inactive'">1</div>
            <div class="h-[1px] w-28 bg-[#F3F4F6]"></div>
            <div class="step-indicator" :class="step === 2 ? 'step-active' : 'step-inactive'">2</div>
        </div>

        @if($errors->any())
            <div class="mb-8 p-4 bg-red-50 border border-red-100 rounded-2xl text-[11px] font-bold text-red-600 text-center uppercase tracking-wider">
                {{ $errors->first() }}
            </div>
        @endif

        <form action="{{ route('seller.register.submit') }}" method="POST" enctype="multipart/form-data">
            @csrf
            
            <!-- STEP 1 -->
            <div x-show="step === 1" class="space-y-6">
                <div class="space-y-1">
                    <label class="text-[10px] font-bold uppercase tracking-widest px-5 block text-gray-400">Registry Name</label>
                    <div class="relative">
                        <span class="absolute left-8 top-1/2 -translate-y-1/2 opacity-20">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" /></svg>
                        </span>
                        <input type="text" name="name" value="{{ old('name') }}" required class="premium-input pl-16" placeholder="Your Full Name">
                    </div>
                </div>

                <div class="space-y-1">
                    <label class="text-[10px] font-bold uppercase tracking-widest px-5 block text-gray-400">Secure Email</label>
                    <div class="relative">
                        <span class="absolute left-8 top-1/2 -translate-y-1/2 opacity-20">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002 2H5a2 2 0 00-2-2V7a2 2 0 002-2h14a2 2 0 002 2v10" /></svg>
                        </span>
                        <input type="email" name="email" value="{{ old('email') }}" required class="premium-input pl-16" placeholder="email@example.com">
                    </div>
                </div>

                <div class="space-y-1">
                    <label class="text-[10px] font-bold uppercase tracking-widest px-5 block text-gray-400">Platform Password</label>
                    <div class="relative" x-data="{ show: false }">
                        <span class="absolute left-8 top-1/2 -translate-y-1/2 opacity-20">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" /></svg>
                        </span>
                        <input :type="show ? 'text' : 'password'" name="password" required class="premium-input pl-16 pr-16" placeholder="••••••••••••">
                        <button type="button" @click="show = !show" class="absolute right-8 top-1/2 -translate-y-1/2 opacity-40 hover:opacity-100">
                            <svg x-show="!show" xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" /><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" /></svg>
                            <svg x-show="show" xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.878 9.878L5.136 5.136m13.727 13.727L13.875 18.825M21 12a10.025 10.025 0 01-1.12 4.5m-5.878-9.375l2.122-2.122m-8.484 8.484L5.136 5.136m13.727 13.727L21 12" /></svg>
                        </button>
                    </div>
                </div>

                <div class="space-y-1">
                    <label class="text-[10px] font-bold uppercase tracking-widest px-5 block text-gray-400">Confirm Password</label>
                    <div class="relative">
                        <span class="absolute left-8 top-1/2 -translate-y-1/2 opacity-20">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" /></svg>
                        </span>
                        <input type="password" name="password_confirmation" required class="premium-input pl-16" placeholder="••••••••••••">
                    </div>
                </div>

                <button type="button" @click="step = 2" class="w-full h-14 bg-[#3D2B1F] text-white rounded-full font-bold uppercase tracking-[0.2em] text-[11px] shadow-xl shadow-black/10 hover:bg-[#C0422A] transition-all flex items-center justify-center gap-3">
                    Continue
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3" /></svg>
                </button>


            </div>

            <!-- STEP 2 -->
            <div x-show="step === 2" class="space-y-6">
                <div class="text-center mb-6">
                    <h2 class="text-xs font-bold uppercase tracking-widest text-gray-500 mb-1">Seller Verification</h2>
                    <p class="text-[10px] text-gray-400 italic">Please provide your details for account verification.</p>
                </div>

                <div class="space-y-1">
                    <label class="text-[10px] font-bold uppercase tracking-widest px-5 block text-gray-400">Mobile Number</label>
                    <input type="text" name="mobileNumber" value="{{ old('mobileNumber') }}" class="premium-input px-8" placeholder="09xx-xxx-xxxx">
                </div>

                <div class="space-y-1">
                    <label class="text-[10px] font-bold uppercase tracking-widest px-5 block text-gray-400">Requirements</label>
                    <div class="grid grid-cols-3 gap-4">
                        <!-- Indigency -->
                        <div class="relative" x-data="{ fileName: '' }">
                            <input type="file" name="indigencyCertificate" @change="fileName = $event.target.files[0].name" class="absolute inset-0 w-full h-full opacity-0 cursor-pointer z-10" required>
                            <div class="upload-card">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-[#C0422A] mb-2" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12" /></svg>
                                <span class="text-[8px] font-bold uppercase tracking-widest text-gray-400 text-center" x-text="fileName || 'Indigency'"></span>
                            </div>
                        </div>

                        <!-- Valid ID -->
                        <div class="relative" x-data="{ fileName: '' }">
                            <input type="file" name="validId" @change="fileName = $event.target.files[0].name" class="absolute inset-0 w-full h-full opacity-0 cursor-pointer z-10" required>
                            <div class="upload-card">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-[#C0422A] mb-2" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12" /></svg>
                                <span class="text-[8px] font-bold uppercase tracking-widest text-gray-400 text-center" x-text="fileName || 'Valid ID'"></span>
                            </div>
                        </div>

                        <!-- GCash QR -->
                        <div class="relative" x-data="{ fileName: '' }">
                            <input type="file" name="gcashQrCode" @change="fileName = $event.target.files[0].name" class="absolute inset-0 w-full h-full opacity-0 cursor-pointer z-10" required>
                            <div class="upload-card">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-[#C0422A] mb-2" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12" /></svg>
                                <span class="text-[8px] font-bold uppercase tracking-widest text-gray-400 text-center" x-text="fileName || 'GCash QR'"></span>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="pt-4">
                    <button type="submit" class="w-full h-14 bg-[#C0422A] text-white rounded-full font-bold uppercase tracking-[0.2em] text-[11px] shadow-xl shadow-[#C0422A]/20 hover:scale-[1.02] transition-all">
                        Sign Up
                    </button>
                </div>
            </div>
        </form>

        <div class="mt-10 text-center">
            <p class="text-[10px] font-bold uppercase tracking-widest text-gray-400">
                Already registered? 
                <a href="/login" class="text-[#C0422A] ml-1">Sign-In</a>
            </p>
        </div>
    </div>
</body>
</html>
