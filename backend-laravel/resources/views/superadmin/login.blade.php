<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Super Admin Login | LumBarong</title>
    <!-- Favicon -->
    <link rel="icon" type="image/x-icon" href="{{ asset('favicon.ico') }}">
    <link rel="apple-touch-icon" sizes="180x180" href="{{ asset('images/apple-touch-icon.png') }}">
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=Playfair+Display:wght@700;800&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Inter', sans-serif; background: #F7F3EE; }
        .font-serif { font-family: 'Playfair Display', serif; }
        [x-cloak] { display: none !important; }
    </style>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.14.8/dist/cdn.min.js"></script>
</head>
<body class="min-h-screen flex items-center justify-center p-6 relative overflow-hidden">
    <!-- Subtle warm blobs -->
    <div class="absolute top-0 right-0 w-140 h-140 rounded-full -translate-y-1/2 translate-x-1/3 blur-3xl opacity-[0.04] pointer-events-none bg-[#C0422A]"></div>
    <div class="absolute bottom-0 left-0 w-95 h-95 rounded-full translate-y-1/2 -translate-x-1/3 blur-3xl opacity-[0.12] pointer-events-none bg-[#D4B896]"></div>

    <div class="w-full max-w-md bg-white rounded-[2.5rem] border border-[#E5DDD5] p-8 shadow-[0_20px_60px_rgba(60,40,20,0.08)] relative z-10">
        <div class="relative mb-8 text-center">
            <a href="/" class="absolute left-0 top-1/2 -translate-y-1/2 w-10 h-10 rounded-full bg-[#F9F6F2] flex items-center justify-center hover:bg-gray-100 text-gray-600 hover:text-[#C0422A] transition-all shadow-sm" title="Back to Home">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                </svg>
            </a>
            <div class="flex justify-center mb-3">
                <img src="{{ asset('images/logo-icon.png') }}" alt="LumBarong Logo" class="w-14 h-14 object-contain rounded-full shadow-md hover:scale-105 transition-transform">
            </div>
            <h1 class="font-serif text-2xl font-black tracking-tight text-gray-900 mb-1">LumBarong</h1>
            <p class="text-[11px] font-bold uppercase tracking-[0.3em] text-gray-400">Super Admin Portal</p>
        </div>

        @if(session('error'))
        <div class="mb-4 p-4 rounded-2xl bg-red-50 border border-red-200 text-red-700 text-xs font-medium flex items-center gap-3 shadow-sm">
            <div class="w-6 h-6 rounded-full bg-red-100 text-red-600 flex items-center justify-center shrink-0 font-bold text-xs">!</div>
            <p class="leading-relaxed text-red-600 font-bold text-xs">{{ session('error') }}</p>
        </div>
        @endif

        @if($errors->any())
        <div class="mb-4 p-4 rounded-2xl bg-red-50 border border-red-200 text-red-700 text-xs font-medium flex items-center gap-3 shadow-sm">
            <div class="w-6 h-6 rounded-full bg-red-100 text-red-600 flex items-center justify-center shrink-0 font-bold text-xs">!</div>
            <p class="leading-relaxed text-red-600 font-bold text-xs">{{ $errors->first() }}</p>
        </div>
        @endif

        <form action="{{ route('superadmin.login.submit') }}" method="POST" class="space-y-6">
            @csrf
            <div class="space-y-2">
                <label class="text-[10px] font-bold uppercase tracking-widest px-5 block text-gray-500">Super Admin Email</label>
                <input
                    type="email"
                    name="email"
                    value="{{ old('email') }}"
                    required
                    autofocus
                    placeholder="superadmin@lumbarong.com"
                    class="w-full h-14 bg-[#F9F6F2] rounded-full px-8 text-sm font-medium border-2 border-transparent focus:border-[#C0422A] focus:bg-white outline-none transition-all"
                >
            </div>

            <div class="space-y-2" x-data="{ show: false }">
                <label class="text-[10px] font-bold uppercase tracking-widest px-5 block text-gray-500">Password</label>
                <div class="relative">
                    <input
                        :type="show ? 'text' : 'password'"
                        name="password"
                        required
                        placeholder="••••••••"
                        class="w-full h-14 bg-[#F9F6F2] rounded-full px-8 pr-14 text-sm font-medium border-2 border-transparent focus:border-[#C0422A] focus:bg-white outline-none transition-all"
                    >
                    <button type="button" @click="show = !show"
                        class="absolute right-5 top-1/2 -translate-y-1/2 text-gray-400 hover:text-[#C0422A] transition-colors">
                        <svg x-show="!show" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                        </svg>
                        <svg x-show="show" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" x-cloak>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.477 0-8.268-2.943-9.542-7a9.97 9.97 0 012.458-4.017M6.53 6.53A9.97 9.97 0 0112 5c4.478 0 8.268 2.943 9.542 7a10.05 10.05 0 01-4.132 5.411M15 12a3 3 0 00-3-3m0 0a3 3 0 00-3 3m3-3L3 3m18 18L9 9"/>
                        </svg>
                    </button>
                </div>
            </div>

            <button type="submit" class="w-full h-14 bg-[#3D2B1F] text-white rounded-full font-bold uppercase tracking-[0.2em] text-[10px] shadow-xl shadow-black/10 hover:bg-[#C0422A] transition-all">
                Sign In to Super Admin
            </button>
        </form>

        <div class="mt-10 text-center">
            <a href="/" class="text-[10px] font-bold uppercase tracking-widest text-[#C0422A]">← Back to Main Platform</a>
        </div>
    </div>
</body>
</html>
