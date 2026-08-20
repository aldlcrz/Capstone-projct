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
    x-cloak
    class="fixed inset-0 z-50 flex items-center justify-center p-4" 
    style="display: none;"
    x-transition:enter="transition ease-out duration-300"
    x-transition:enter-start="opacity-0 scale-90"
    x-transition:enter-end="opacity-100 scale-100"
    x-transition:leave="transition ease-in duration-200"
    x-transition:leave-start="opacity-100 scale-100"
    x-transition:leave-end="opacity-0 scale-90"
>
    <!-- Backdrop -->
    <div @click="isOpen = false" class="absolute inset-0 bg-black/60 backdrop-blur-sm"></div>
    
    <!-- Modal Container -->
    <div class="bg-white w-full max-w-sm rounded-3xl shadow-none relative z-10 overflow-hidden border border-gray-200">
        <div class="p-6 sm:p-8 text-center flex flex-col items-center">
            
            <!-- Icon Container -->
            <div :class="{
                'bg-red-50 border-red-100 text-red-600': type === 'danger',
                'bg-amber-50 border-amber-100 text-amber-600': type === 'warning',
                'bg-orange-50 border-orange-100 text-[#C0420A]': type === 'info'
            }" class="w-16 h-16 rounded-2xl border flex items-center justify-center mb-5">
                <template x-if="type === 'danger'">
                    <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"></path>
                    </svg>
                </template>
                <template x-if="type === 'warning'">
                    <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
                    </svg>
                </template>
                <template x-if="type === 'info'">
                    <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                </template>
            </div>
            
            <!-- Text Content -->
            <div class="space-y-1.5 mb-6">
                <h3 class="text-xl font-bold text-gray-900 tracking-tight" x-text="title"></h3>
                <p class="text-xs text-gray-500 font-medium leading-relaxed px-2" x-text="message"></p>
            </div>

            <!-- Action Buttons -->
            <div class="w-full flex items-center gap-3">
                <button 
                    type="button"
                    @click="isOpen = false" 
                    class="flex-1 py-3 px-4 text-xs font-bold text-gray-700 bg-gray-100 hover:bg-gray-200 rounded-xl transition-all cursor-pointer"
                >
                    <span x-text="cancelText"></span>
                </button>

                <button 
                    type="button"
                    @click="if(onConfirm) onConfirm(); isOpen = false" 
                    :class="{
                        'bg-red-600 hover:bg-red-700 text-white': type === 'danger',
                        'bg-amber-600 hover:bg-amber-700 text-white': type === 'warning',
                        'bg-[#C0420A] hover:bg-[#a53808] text-white': type === 'info'
                    }" 
                    class="flex-1 py-3 px-4 text-xs font-bold text-white rounded-xl transition-all active:scale-95 cursor-pointer shadow-none border-0"
                >
                    <span x-text="confirmText"></span>
                </button>
            </div>
        </div>
    </div>
</div>
