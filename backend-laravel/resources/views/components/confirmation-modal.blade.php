<div x-data="{ 
        isOpen: false, 
        title: '', 
        message: '', 
        type: 'danger', 
        confirmText: 'Confirm', 
        cancelText: 'Cancel', 
        onConfirm: null 
    }" 
    @open-confirmation.window="
        isOpen = true; 
        title = $event.detail.title || 'Are you sure?'; 
        message = $event.detail.message || 'This action cannot be undone.'; 
        type = $event.detail.type || 'danger';
        confirmText = $event.detail.confirmText || 'Confirm';
        cancelText = $event.detail.cancelText || 'Cancel';
        onConfirm = $event.detail.onConfirm;
    "
    x-show="isOpen" 
    class="fixed inset-0 z-[999] flex items-center justify-center p-4" 
    style="display: none;"
    x-transition:enter="transition ease-out duration-300"
    x-transition:enter-start="opacity-0 scale-90"
    x-transition:enter-end="opacity-100 scale-100"
    x-transition:leave="transition ease-in duration-200"
    x-transition:leave-start="opacity-100 scale-100"
    x-transition:leave-end="opacity-0 scale-90"
>
    <!-- Backdrop -->
    <div @click="isOpen = false" class="absolute inset-0 bg-[#1A1208]/40 backdrop-blur-md"></div>
    
    <!-- Modal Container -->
    <div class="bg-white w-full max-w-[400px] rounded-[2.5rem] shadow-[0_32px_80px_rgba(26,18,8,0.25)] relative z-10 overflow-hidden border border-gray-100">
        <div class="p-10 text-center flex flex-col items-center">
            
            <!-- Icon Container -->
            <div :class="{
                'bg-red-100 border-red-200 text-red-600': type === 'danger',
                'bg-amber-100 border-amber-200 text-amber-600': type === 'warning',
                'bg-orange-100 border-orange-200 text-[#C0420A]': type === 'info'
            }" class="w-20 h-20 rounded-[2rem] border flex items-center justify-center mb-8 shadow-inner">
                <template x-if="type === 'danger'">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"></path>
                    </svg>
                </template>
                <template x-if="type === 'warning'">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
                    </svg>
                </template>
                <template x-if="type === 'info'">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                </template>
            </div>
            
            <!-- Text Content -->
            <div class="space-y-3 mb-10">
                <h3 class="font-serif text-2xl font-bold text-[#1C1209] tracking-tight" x-text="title"></h3>
                <p class="text-[13px] text-gray-500 font-medium leading-relaxed px-4" x-text="message"></p>
            </div>

            <!-- Action Buttons -->
            <div class="w-full flex flex-col gap-3">
                <button 
                    @click="if(onConfirm) onConfirm(); isOpen = false" 
                    :class="{
                        'from-red-500 to-rose-600 shadow-red-200': type === 'danger',
                        'from-amber-500 to-yellow-600 shadow-amber-200': type === 'warning',
                        'from-[#C0420A] to-[#A63924] shadow-orange-200': type === 'info'
                    }" 
                    class="w-full py-4 bg-gradient-to-r text-white rounded-2xl text-[11px] font-bold uppercase tracking-[0.25em] transition-all shadow-xl hover:-translate-y-0.5 active:scale-95"
                >
                    <span x-text="confirmText"></span>
                </button>
                <button 
                    @click="isOpen = false" 
                    class="w-full py-4 text-[11px] font-bold text-gray-400 uppercase tracking-[0.2em] hover:text-black hover:bg-[#F7F3EE] rounded-2xl transition-all"
                >
                    <span x-text="cancelText"></span>
                </button>
            </div>
        </div>
        
        <!-- Bottom Decorative Bar -->
        <div :class="{
            'from-red-500 to-rose-600': type === 'danger',
            'from-amber-500 to-yellow-600': type === 'warning',
            'from-[#C0420A] to-[#A63924]': type === 'info'
        }" class="h-1.5 w-full bg-gradient-to-r"></div>
    </div>
</div>
