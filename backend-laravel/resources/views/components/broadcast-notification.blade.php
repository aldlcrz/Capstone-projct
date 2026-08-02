<div x-data="{ 
        broadcast: null, 
        timer: null,
        init() {
            window.addEventListener('broadcast-received', (e) => {
                this.broadcast = e.detail;
                if (this.timer) clearTimeout(this.timer);
                this.timer = setTimeout(() => this.broadcast = null, 10000);
            });
            
            // Example of how to trigger from console for testing:
            // window.dispatchEvent(new CustomEvent('broadcast-received', { detail: { title: 'System Update', message: 'The artisan registry is currently being synchronized.' } }));
        }
    }" 
    x-show="broadcast" 
    x-transition:enter="transition ease-out duration-300"
    x-transition:enter-start="opacity-0 -translate-y-10"
    x-transition:enter-end="opacity-100 translate-y-0"
    x-transition:leave="transition ease-in duration-200"
    x-transition:leave-start="opacity-100 translate-y-0"
    x-transition:leave-end="opacity-0 -translate-y-10"
    class="fixed top-6 left-0 right-0 z-[100] flex justify-center px-4 pointer-events-none" 
    style="display: none;"
>
    <div class="pointer-events-auto bg-white border border-gray-100 rounded-2xl shadow-[0_20px_50px_rgba(0,0,0,0.15)] flex items-stretch overflow-hidden max-w-xl w-full min-h-[80px]">
        <!-- Accent Side Bar -->
        <div class="w-2 bg-[#C0422A] shrink-0"></div>
        
        <div class="flex-1 p-5 flex items-start gap-4 relative">
            <div class="w-10 h-10 rounded-xl bg-red-50 flex items-center justify-center text-[#C0422A] shrink-0">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"></path>
                </svg>
            </div>
            
            <div class="flex-1 min-w-0 pr-6">
                <div class="flex items-center gap-2 mb-1">
                    <span class="text-[10px] font-bold text-[#C0422A] uppercase tracking-widest" x-text="broadcast?.title || 'Announcement'"></span>
                    <span class="w-1 h-1 bg-gray-200 rounded-full"></span>
                    <span class="text-[10px] font-bold text-gray-400 uppercase tracking-widest">Real-time</span>
                </div>
                <p class="text-sm font-semibold text-black leading-relaxed" x-text="broadcast?.message"></p>
            </div>
            
            <button 
                @click="broadcast = null"
                class="absolute top-4 right-4 p-2 text-gray-400 hover:text-[#C0422A] hover:bg-gray-50 rounded-lg transition-all"
            >
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                </svg>
            </button>
        </div>
    </div>
</div>
