<div x-data="{ 
        isOpen: false, 
        message: '', 
        redirectPath: '' 
    }" 
    @open-auth-gate.window="
        isOpen = true; 
        message = $event.detail.message || 'Please log in or sign up to continue with this action.'; 
        redirectPath = $event.detail.redirectPath || window.location.pathname + window.location.search;
    "
    x-show="isOpen" 
    class="fixed inset-0 z-[1000] flex items-center justify-center p-4" 
    style="display: none;"
    x-transition:enter="transition ease-out duration-300"
    x-transition:enter-start="opacity-0 scale-95"
    x-transition:enter-end="opacity-100 scale-100"
    x-transition:leave="transition ease-in duration-200"
    x-transition:leave-start="opacity-100 scale-100"
    x-transition:leave-end="opacity-0 scale-95"
>
    <!-- Backdrop -->
    <div @click="isOpen = false" class="absolute inset-0 bg-black/40 backdrop-blur-sm"></div>
    
    <!-- Modal Content -->
    <div class="relative bg-white w-full max-w-md rounded-[2.5rem] shadow-2xl overflow-hidden p-8 text-center border border-gray-100">
        <button @click="isOpen = false" class="absolute top-6 right-6 p-2 bg-gray-50 rounded-xl text-gray-400 hover:text-red-500 transition-colors">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
            </svg>
        </button>

        <div class="w-16 h-16 bg-red-50 rounded-2xl flex items-center justify-center mx-auto mb-6 text-[#C0420A]">
            <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m0 0v2m0-2h2m-2 0H10m4-6a4 4 0 11-8 0 4 4 0 018 0z"></path>
            </svg>
        </div>

        <h2 class="font-serif text-2xl font-bold text-black mb-3">Authentication Required</h2>
        <p class="text-sm text-gray-500 font-medium leading-relaxed mb-8" x-text="message"></p>

        <div class="space-y-3">
            <button 
                @click="window.location.href = '/login?redirect=' + encodeURIComponent(redirectPath)" 
                class="w-full bg-[#C0420A] text-white py-4 rounded-xl font-bold uppercase text-[10px] tracking-[0.2em] shadow-lg shadow-red-100 hover:scale-[1.02] active:scale-[0.98] transition-all flex items-center justify-center gap-3"
            >
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"></path></svg>
                Sign In
            </button>
            <button 
                @click="window.location.href = '/register?redirect=' + encodeURIComponent(redirectPath)" 
                class="w-full bg-white border-2 border-gray-100 text-black py-4 rounded-xl font-bold uppercase text-[10px] tracking-[0.2em] hover:bg-gray-50 transition-all flex items-center justify-center gap-3"
            >
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"></path></svg>
                Create Account
            </button>
        </div>

        <p class="mt-8 text-[10px] font-bold text-gray-400 uppercase tracking-widest opacity-60">
            Browse freely. Transact securely.
        </p>
    </div>
</div>
