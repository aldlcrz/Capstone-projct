<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Maintenance in Progress - LumBarong</title>
    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&family=Playfair+Display:ital,wght@0,600;0,700;1,400&display=swap" rel="stylesheet">
    
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: {
                        sans: ['Inter', 'sans-serif'],
                        serif: ['Playfair Display', 'serif'],
                    }
                }
            }
        }
    </script>

    <style>
        @keyframes pulse-glow {
            0%, 100% {
                transform: scale(1);
                opacity: 0.15;
                filter: blur(40px);
            }
            50% {
                transform: scale(1.1);
                opacity: 0.25;
                filter: blur(60px);
            }
        }
        .glow-effect {
            animation: pulse-glow 8s infinite ease-in-out;
        }
    </style>
</head>
<body class="bg-[#121212] text-[#E3D9C9] font-sans antialiased min-h-screen flex flex-col justify-between relative overflow-hidden selection:bg-[#C0422A] selection:text-white">
    
    <!-- Decorative Ambient Glows -->
    <div class="absolute top-1/4 left-1/2 -translate-x-1/2 -translate-y-1/2 w-[350px] h-[350px] sm:w-[500px] sm:h-[500px] rounded-full bg-[#C0422A] glow-effect pointer-events-none z-0"></div>
    <div class="absolute bottom-0 right-0 w-[300px] h-[300px] rounded-full bg-[#A89880] opacity-5 blur-[80px] pointer-events-none z-0"></div>

    <!-- Header / Logo -->
    <header class="relative z-10 w-full px-6 py-8 flex justify-center border-b border-white/5 backdrop-blur-sm bg-black/10">
        <div class="flex items-center gap-3">
            <div class="w-9 h-9 bg-white text-black rounded-full flex items-center justify-center font-bold text-xl shadow-lg shadow-white/5 transition-transform hover:scale-105">L</div>
            <span class="text-xl font-bold tracking-widest uppercase text-white font-sans">LumBarong</span>
        </div>
    </header>

    <!-- Main Content -->
    <main class="relative z-10 flex-1 flex flex-col items-center justify-center text-center px-6 py-12 max-w-2xl mx-auto w-full">
        <!-- Artisan Icon Container -->
        <div class="relative mb-8 group">
            <div class="absolute -inset-1 rounded-full opacity-40 blur-sm group-hover:opacity-75 transition duration-500" style="background-image: linear-gradient(to right, #C0422A, #A89880);"></div>
            <div class="relative w-20 h-20 rounded-full bg-[#1A1A1A] border border-white/10 flex items-center justify-center text-[#C0422A] shadow-2xl">
                <svg class="w-10 h-10 animate-pulse" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"></path>
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                </svg>
            </div>
        </div>

        <!-- Title -->
        <h1 class="font-serif text-4xl sm:text-5xl font-bold text-white tracking-tight leading-tight mb-6">
            Improving Your <br class="sm:hidden">
            <span class="text-transparent bg-clip-text" style="background-image: linear-gradient(to right, #E3D9C9, #ffffff, #A89880); -webkit-background-clip: text; background-clip: text;">Artisan Experience</span>
        </h1>

        <!-- Subtitle/Message -->
        <div class="bg-white/5 border border-white/10 rounded-2xl p-6 backdrop-blur-md shadow-xl max-w-md w-full mb-8">
            <p class="text-sm sm:text-base leading-relaxed text-gray-300 font-medium">
                {{ $message }}
            </p>
        </div>

        <!-- Status Indicator -->
        <div class="inline-flex items-center gap-2 px-4 py-2 rounded-full bg-white/5 border border-white/10 text-xs font-semibold uppercase tracking-wider text-gray-400">
            <span class="w-2.5 h-2.5 rounded-full bg-[#C0422A] animate-ping"></span>
            Undergoing Maintenance
        </div>
    </main>

    <!-- Footer -->
    <footer class="relative z-10 w-full px-6 py-8 border-t border-white/5 text-center flex flex-col sm:flex-row items-center justify-between gap-4 max-w-[1440px] mx-auto text-xs text-gray-500 font-medium bg-black/10">
        <div>
            LumBarong &copy; 2026. Handcrafted Heritage & Artistry.
        </div>
        <div class="flex items-center gap-6">
            <a href="/login" class="hover:text-white transition-colors duration-200 underline decoration-white/20 underline-offset-4 hover:decoration-white font-semibold">
                System Administration Login &rarr;
            </a>
        </div>
    </footer>

</body>
</html>
