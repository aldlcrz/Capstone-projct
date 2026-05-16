<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register | LumBarong</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=Playfair+Display:wght@700;800&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Inter', sans-serif; background: #F7F3EE; }
        .font-serif { font-family: 'Playfair Display', serif; }
    </style>
</head>
<body class="min-h-screen flex items-center justify-center p-6 relative overflow-hidden">
    <!-- Subtle warm blobs -->
    <div class="absolute top-0 right-0 w-[560px] h-[560px] rounded-full -translate-y-1/2 translate-x-1/3 blur-3xl opacity-[0.04] pointer-events-none bg-[#C0422A]"></div>
    <div class="absolute bottom-0 left-0 w-[380px] h-[380px] rounded-full translate-y-1/2 -translate-x-1/3 blur-3xl opacity-[0.12] pointer-events-none bg-[#D4B896]"></div>

    <div class="w-full max-w-md bg-white rounded-[2.5rem] border border-[#E5DDD5] p-8 shadow-[0_20px_60px_rgba(60,40,20,0.08)] relative z-10">
        <div class="mb-10 text-center">
            <h1 class="font-serif text-2xl font-black italic tracking-tight text-[#C0422A] mb-1">LumBarong</h1>
            <p class="text-[11px] font-bold uppercase tracking-[0.3em] text-gray-400">Join the Collective</p>
        </div>

        @if($errors->any())
            <div class="mb-8 p-4 bg-red-50 border border-red-100 rounded-2xl text-[11px] font-bold text-red-600 text-center uppercase tracking-wider">
                {{ $errors->first() }}
            </div>
        @endif

        <form action="/register" method="POST" class="space-y-4">
            @csrf
            <div class="space-y-1">
                <label class="text-[10px] font-bold uppercase tracking-widest px-5 block text-gray-500">Username</label>
                <input 
                    type="text" 
                    name="username" 
                    value="{{ old('username') }}"
                    required 
                    class="w-full h-12 bg-[#F9F6F2] rounded-full px-8 text-sm font-medium border-2 border-transparent focus:border-[#C0422A] focus:bg-white outline-none transition-all"
                >
            </div>

            <div class="space-y-1">
                <label class="text-[10px] font-bold uppercase tracking-widest px-5 block text-gray-500">Email Address</label>
                <input 
                    type="email" 
                    name="email" 
                    value="{{ old('email') }}"
                    required 
                    class="w-full h-12 bg-[#F9F6F2] rounded-full px-8 text-sm font-medium border-2 border-transparent focus:border-[#C0422A] focus:bg-white outline-none transition-all"
                >
            </div>

            <div class="space-y-1">
                <label class="text-[10px] font-bold uppercase tracking-widest px-5 block text-gray-500">Password</label>
                <input 
                    type="password" 
                    name="password" 
                    required 
                    class="w-full h-12 bg-[#F9F6F2] rounded-full px-8 text-sm font-medium border-2 border-transparent focus:border-[#C0422A] focus:bg-white outline-none transition-all"
                >
            </div>

            <div class="space-y-1">
                <label class="text-[10px] font-bold uppercase tracking-widest px-5 block text-gray-500">Confirm Password</label>
                <input 
                    type="password" 
                    name="password_confirmation" 
                    required 
                    class="w-full h-12 bg-[#F9F6F2] rounded-full px-8 text-sm font-medium border-2 border-transparent focus:border-[#C0422A] focus:bg-white outline-none transition-all"
                >
            </div>

            <button type="submit" class="w-full h-14 bg-[#3D2B1F] text-white rounded-full font-bold uppercase tracking-[0.2em] text-[10px] shadow-xl shadow-black/10 hover:bg-[#C0422A] transition-all mt-4">
                Register
            </button>
        </form>

        <div class="mt-10 text-center">
            <p class="text-[10px] font-bold uppercase tracking-widest text-gray-400">
                Already have an account? 
                <a href="/login" class="text-[#C0422A] ml-1">Log-In</a>
            </p>
        </div>
    </div>
</body>
</html>