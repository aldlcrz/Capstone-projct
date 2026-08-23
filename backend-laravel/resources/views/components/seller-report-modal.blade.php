<div x-data="sellerReportModal()" 
    @open-seller-report.window="open($event.detail)"
    x-show="isOpen" 
    class="fixed inset-0 z-1000 flex items-center justify-center p-4" 
    style="display: none;"
    x-transition:enter="transition ease-out duration-300"
    x-transition:enter-start="opacity-0 scale-95"
    x-transition:enter-end="opacity-100 scale-100"
    x-transition:leave="transition ease-in duration-200"
    x-transition:leave-start="opacity-100 scale-100"
    x-transition:leave-end="opacity-0 scale-95"
    x-cloak
>
    <!-- Backdrop -->
    <div @click="isOpen = false" class="absolute inset-0 bg-black/60 backdrop-blur-sm"></div>
    
    <!-- Modal Content -->
    <div class="relative w-full max-w-lg bg-white rounded-3xl sm:rounded-4xl shadow-2xl overflow-hidden p-0 border border-gray-100 max-h-[90vh] flex flex-col">
        <!-- Header Banner -->
        <div class="p-6 sm:p-8 bg-gradient-to-r from-amber-500/10 via-red-500/10 to-transparent border-b border-gray-100">
            <div class="flex justify-between items-start">
                <div class="space-y-1">
                    <div class="inline-flex items-center gap-1.5 px-3 py-1 bg-amber-100 text-amber-900 rounded-full text-[9px] font-black uppercase tracking-widest">
                        <svg class="w-3.5 h-3.5 text-amber-700" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                        <span>Integrity Notice</span>
                    </div>
                    <h3 class="font-serif text-xl sm:text-2xl font-bold text-black tracking-tight uppercase mt-1">
                        Shop <span class="text-[#C0420A] italic lowercase">Report Details</span>
                    </h3>
                    <p class="text-[9px] font-bold text-gray-400 uppercase tracking-widest" x-text="'Filed: ' + (report.formattedDate || 'Recent')"></p>
                </div>
                <button @click="isOpen = false" class="p-2 hover:bg-gray-100 rounded-xl transition-all text-gray-400 hover:text-black">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>
        </div>

        <!-- Body Content -->
        <div class="p-6 sm:p-8 space-y-6 overflow-y-auto flex-1">
            <template x-if="isLoading">
                <div class="py-12 text-center space-y-3">
                    <div class="w-8 h-8 border-3 border-[#C0420A] border-t-transparent rounded-full animate-spin mx-auto"></div>
                    <div class="text-[10px] font-bold text-gray-400 uppercase tracking-widest">Loading report details...</div>
                </div>
            </template>

            <template x-if="!isLoading">
                <div class="space-y-5 text-left">
                    <!-- Reason Card -->
                    <div class="p-4 bg-gray-50/80 rounded-2xl border border-gray-100 space-y-1">
                        <div class="text-[9px] font-black uppercase tracking-widest text-gray-400">Report Category</div>
                        <div class="text-sm font-black text-[#C0420A]" x-text="report.reason || 'General Report'"></div>
                    </div>

                    <!-- Customer Description -->
                    <div class="p-4 bg-gray-50/80 rounded-2xl border border-gray-100 space-y-1.5">
                        <div class="text-[9px] font-black uppercase tracking-widest text-gray-400">Customer's Statement</div>
                        <p class="text-xs text-gray-700 leading-relaxed font-medium whitespace-pre-line" x-text="report.description || 'No detailed description provided.'"></p>
                    </div>

                    <!-- Attached Evidence -->
                    <template x-if="report.evidence">
                        <div class="p-4 bg-gray-50/80 rounded-2xl border border-gray-100 space-y-2">
                            <div class="text-[9px] font-black uppercase tracking-widest text-gray-400">Submitted Evidence</div>
                            <div class="relative group rounded-xl overflow-hidden border border-gray-200 max-w-xs bg-white shadow-xs">
                                <img :src="report.evidence" alt="Evidence" class="w-full h-40 object-cover group-hover:scale-105 transition-transform duration-300 cursor-pointer" @click="window.open(report.evidence, '_blank')">
                                <a :href="report.evidence" target="_blank" class="absolute bottom-2 right-2 px-3 py-1 bg-black/75 hover:bg-black text-white text-[9px] font-bold uppercase tracking-wider rounded-lg backdrop-blur-xs flex items-center gap-1 transition-all">
                                    <span>View Full</span>
                                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/></svg>
                                </a>
                            </div>
                        </div>
                    </template>

                    <!-- Status & Review Notice -->
                    <div class="p-4 bg-amber-50/60 border border-amber-200/60 rounded-2xl space-y-1.5">
                        <div class="flex items-center gap-2">
                            <div class="w-2 h-2 rounded-full bg-amber-500 animate-ping"></div>
                            <div class="text-[10px] font-black uppercase tracking-wider text-amber-900">
                                Review Status: <span class="text-[#C0420A]" x-text="report.status || 'Under Review'"></span>
                            </div>
                        </div>
                        <p class="text-[11px] text-amber-950/80 leading-relaxed">
                            Our Trust & Safety team will objectively review your shop transactions and communications. No automatic penalty is applied. If further clarification is needed, platform administrators will reach out.
                        </p>
                    </div>
                </div>
            </template>
        </div>

        <!-- Footer -->
        <div class="p-5 sm:p-6 bg-gray-50/80 border-t border-gray-100 flex justify-end">
            <button 
                type="button" 
                @click="isOpen = false" 
                class="w-full sm:w-auto px-8 py-3.5 bg-black hover:bg-[#C0420A] text-white text-[10px] font-black uppercase tracking-[0.2em] rounded-xl transition-all shadow-md active:scale-95 cursor-pointer"
            >
                Acknowledge Notice
            </button>
        </div>
    </div>
</div>

<script>
function sellerReportModal() {
    return {
        isOpen: false,
        isLoading: false,
        report: {
            id: '',
            reason: '',
            description: '',
            evidence: '',
            status: 'Under Review',
            formattedDate: ''
        },
        init() {
            // Check for ?view_report= in URL on page load
            const urlParams = new URLSearchParams(window.location.search);
            const reportId = urlParams.get('view_report');
            if (reportId) {
                this.open({ id: reportId });
            }
        },
        async open(detail = {}) {
            this.isOpen = true;
            this.isLoading = true;
            
            const targetId = detail.id || detail.reportId || 'latest';
            
            try {
                const res = await fetch(`/api/v1/seller/reports/${targetId}`);
                if (res.ok) {
                    this.report = await res.json();
                } else {
                    // Fallback to notification data if passed
                    this.report = {
                        reason: detail.reason || 'Integrity Review',
                        description: detail.message || 'A customer has submitted a report regarding your shop.',
                        evidence: '',
                        status: 'Pending Review',
                        formattedDate: 'Recent'
                    };
                }
            } catch (e) {
                console.error(e);
                this.report = {
                    reason: detail.reason || 'Integrity Review',
                    description: detail.message || 'A customer has submitted a report regarding your shop.',
                    evidence: '',
                    status: 'Pending Review',
                    formattedDate: 'Recent'
                };
            }
            this.isLoading = false;
        }
    }
}
</script>
