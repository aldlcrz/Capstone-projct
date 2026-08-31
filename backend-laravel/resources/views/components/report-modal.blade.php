<div x-data="trustSafetyReportModal()" 
    @open-report.window="open($event.detail)"
    x-show="isOpen" 
    class="fixed inset-0 z-1000 flex items-center justify-center p-3 sm:p-4" 
    style="display: none;"
    x-transition:enter="transition ease-out duration-250"
    x-transition:enter-start="opacity-0 scale-95"
    x-transition:enter-end="opacity-100 scale-100"
    x-transition:leave="transition ease-in duration-200"
    x-transition:leave-start="opacity-100 scale-100"
    x-transition:leave-end="opacity-0 scale-95"
    @keydown.escape.window="if(!isSubmitting) isOpen = false"
>
    <!-- Backdrop -->
    <div @click="if(!isSubmitting) isOpen = false" class="absolute inset-0 bg-black/60 backdrop-blur-xs"></div>
    
    <!-- Modal Container -->
    <div class="relative w-full max-w-lg bg-[#FFFCF7] rounded-3xl sm:rounded-4xl shadow-2xl overflow-hidden border border-[#E8DECB] max-h-[92vh] flex flex-col"
         @click.stop>
        
        <!-- Header -->
        <div class="px-6 py-5 sm:px-8 sm:py-6 border-b border-[#E8DECB] bg-[#FDF8EE] flex items-center justify-between shrink-0">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-2xl flex items-center justify-center shrink-0 shadow-xs" style="background: #1E1915; color: #C49520;">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                </div>
                <div>
                    <div class="flex items-center gap-2">
                        <span class="text-[9px] font-black uppercase tracking-widest px-2 py-0.5 rounded-full"
                              :style="reportType === 'product' ? 'background: #EEF3FE; color: #2E5FCA; border: 1px solid #B8CEFA;' : 'background: #FEF9EE; color: #A16D19; border: 1px solid #F6DFA0;'"
                              x-text="reportType === 'product' ? 'PRODUCT REPORT' : 'ACCOUNT REPORT'"></span>
                        <span class="text-[9px] font-bold text-[#A09585] uppercase tracking-wider">Trust &amp; Safety</span>
                    </div>
                    <h3 class="font-serif text-lg sm:text-xl font-bold tracking-tight text-[#1E1915] mt-0.5" x-text="stepTitle()"></h3>
                </div>
            </div>

            <button type="button" @click="if(!isSubmitting) isOpen = false" class="w-8 h-8 rounded-xl flex items-center justify-center text-[#766C60] hover:text-[#1E1915] hover:bg-[#E8DECB]/40 transition-colors cursor-pointer">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>

        <!-- Stepper Indicators (Steps 1 to 4) -->
        <template x-if="currentStep <= 4">
            <div class="px-6 sm:px-8 py-3 bg-[#FAF5EA] border-b border-[#E8DECB] flex items-center justify-between text-[9px] font-bold tracking-wider uppercase text-[#766C60]">
                <div class="flex items-center gap-1.5" :class="currentStep >= 1 ? 'text-[#C49520] font-black' : 'text-[#A09585]'">
                    <span class="w-4 h-4 rounded-full flex items-center justify-center text-[8px]" :style="currentStep >= 1 ? 'background: #C49520; color: #FFF;' : 'background: #E8DECB; color: #766C60;'">1</span>
                    <span>Reason</span>
                </div>
                <span class="text-[#E8DECB]">━━</span>
                <div class="flex items-center gap-1.5" :class="currentStep >= 2 ? 'text-[#C49520] font-black' : 'text-[#A09585]'">
                    <span class="w-4 h-4 rounded-full flex items-center justify-center text-[8px]" :style="currentStep >= 2 ? 'background: #C49520; color: #FFF;' : 'background: #E8DECB; color: #766C60;'">2</span>
                    <span>Details</span>
                </div>
                <span class="text-[#E8DECB]">━━</span>
                <div class="flex items-center gap-1.5" :class="currentStep >= 3 ? 'text-[#C49520] font-black' : 'text-[#A09585]'">
                    <span class="w-4 h-4 rounded-full flex items-center justify-center text-[8px]" :style="currentStep >= 3 ? 'background: #C49520; color: #FFF;' : 'background: #E8DECB; color: #766C60;'">3</span>
                    <span>Evidence</span>
                </div>
                <span class="text-[#E8DECB]">━━</span>
                <div class="flex items-center gap-1.5" :class="currentStep >= 4 ? 'text-[#C49520] font-black' : 'text-[#A09585]'">
                    <span class="w-4 h-4 rounded-full flex items-center justify-center text-[8px]" :style="currentStep >= 4 ? 'background: #C49520; color: #FFF;' : 'background: #E8DECB; color: #766C60;'">4</span>
                    <span>Review</span>
                </div>
            </div>
        </template>

        <!-- Body Content -->
        <div class="p-6 sm:p-8 overflow-y-auto flex-1 space-y-6">
            
            <!-- Error Banner -->
            <template x-if="errorMessage">
                <div class="p-3.5 rounded-2xl bg-red-50 border border-red-200 flex items-start gap-2.5 text-xs text-red-700">
                    <svg class="w-4 h-4 mt-0.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    <span x-text="errorMessage"></span>
                </div>
            </template>

            <!-- ══ STEP 1: Select Reason ══════════════════════════════════ -->
            <div x-show="currentStep === 1" class="space-y-4">
                <div class="p-3.5 rounded-2xl bg-[#FDF8EE] border border-[#E8DECB]">
                    <div class="text-[9px] font-black uppercase tracking-widest text-[#A09585]">Reporting Target</div>
                    <div class="text-sm font-bold text-[#1E1915] mt-0.5 flex items-center gap-2">
                        <span x-text="reportedName"></span>
                        <span x-show="productName" class="text-xs font-normal text-[#766C60]" x-text="'(' + productName + ')'"></span>
                    </div>
                </div>

                <div class="space-y-2">
                    <label class="text-[10px] font-black uppercase tracking-widest text-[#766C60] block">Select Reason for Report</label>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-2 max-h-64 overflow-y-auto pr-1">
                        <template x-for="r in reasonsList()" :key="r">
                            <button type="button" 
                                    @click="selectedReason = r; errorMessage = ''"
                                    :style="selectedReason === r ? 'background: #1E1915; color: #FFF; border-color: #C49520;' : 'background: #FFF; color: #1E1915; border-color: #E8DECB;'"
                                    class="p-3 rounded-xl border text-left text-xs font-bold transition-all flex items-center justify-between gap-2 shadow-2xs hover:border-[#C49520] cursor-pointer">
                                <span x-text="r"></span>
                                <span x-show="selectedReason === r" class="text-[#C49520] font-black">✓</span>
                            </button>
                        </template>
                    </div>
                </div>
            </div>

            <!-- ══ STEP 2: Provide Details ════════════════════════════════ -->
            <div x-show="currentStep === 2" class="space-y-4">
                <div class="p-3.5 rounded-2xl bg-[#FDF8EE] border border-[#E8DECB]">
                    <div class="text-[9px] font-black uppercase tracking-widest text-[#A09585]">Selected Reason</div>
                    <div class="text-xs font-bold text-[#1E1915] mt-0.5" x-text="selectedReason"></div>
                </div>

                <div class="space-y-2">
                    <div class="flex items-center justify-between">
                        <label class="text-[10px] font-black uppercase tracking-widest text-[#766C60]">Detailed Explanation</label>
                        <span class="text-[10px] font-bold tabular-nums" :class="description.trim().length >= 10 ? 'text-emerald-700' : 'text-amber-600'" x-text="description.trim().length + '/10 min chars'"></span>
                    </div>
                    <p class="text-[11px] text-[#766C60] leading-relaxed">
                        Please explain what happened and provide relevant information that can help us investigate this concern.
                    </p>
                    <textarea 
                        x-model="description" 
                        rows="5" 
                        class="w-full p-4 bg-white border border-[#E8DECB] focus:border-[#C49520] rounded-2xl outline-none text-xs font-medium text-[#1E1915] transition-all shadow-2xs resize-none"
                        placeholder="Provide details about dates, order numbers, communications, or specific policy violations observed..."></textarea>
                </div>
            </div>

            <!-- ══ STEP 3: Supporting Evidence ════════════════════════════ -->
            <div x-show="currentStep === 3" class="space-y-4">
                <div>
                    <label class="text-[10px] font-black uppercase tracking-widest text-[#766C60] block">Supporting Evidence (Optional)</label>
                    <p class="text-[11px] text-[#766C60] mt-0.5 leading-relaxed">
                        Upload screenshots, photos of products, receipts, or chat logs to support the investigation (max 10MB per file).
                    </p>
                </div>

                <div class="flex items-center gap-3">
                    <label class="cursor-pointer flex items-center justify-center gap-2 px-5 py-4 border-2 border-dashed border-[#E8DECB] hover:border-[#C49520] rounded-2xl text-xs font-bold text-[#766C60] hover:text-[#1E1915] bg-white transition-all grow text-center shadow-2xs">
                        <svg class="w-5 h-5 text-[#C49520] shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                        <span x-text="isUploading ? 'Uploading file...' : 'Choose image / screenshot to attach...'"></span>
                        <input type="file" accept="image/*" @change="uploadEvidence($event)" class="hidden" :disabled="isUploading">
                    </label>
                </div>

                <!-- Evidence Previews Grid -->
                <template x-if="evidenceFiles.length > 0">
                    <div class="space-y-2">
                        <div class="text-[9px] font-black uppercase tracking-widest text-[#A09585]" x-text="'Attached Evidence (' + evidenceFiles.length + ')'"></div>
                        <div class="grid grid-cols-3 sm:grid-cols-4 gap-2.5">
                            <template x-for="(fileUrl, idx) in evidenceFiles" :key="idx">
                                <div class="relative group rounded-xl overflow-hidden border border-[#E8DECB] aspect-square bg-white shadow-2xs">
                                    <img :src="fileUrl" class="w-full h-full object-cover">
                                    <button type="button" @click="removeEvidence(idx)" class="absolute top-1 right-1 w-5 h-5 rounded-full bg-black/70 text-white flex items-center justify-center hover:bg-red-600 transition-colors text-[10px]">✕</button>
                                </div>
                            </template>
                        </div>
                    </div>
                </template>
            </div>

            <!-- ══ STEP 4: Review Screen ═════════════════════════════════ -->
            <div x-show="currentStep === 4" class="space-y-4">
                <div class="p-4 rounded-2xl bg-[#FAF5EA] border border-[#E8DECB] space-y-3">
                    <div>
                        <span class="text-[9px] font-black uppercase tracking-widest text-[#A09585] block">Report Type</span>
                        <span class="text-xs font-bold text-[#1E1915]" x-text="reportType === 'product' ? 'Product Listing Report' : 'Seller Account Report'"></span>
                    </div>
                    <div class="border-t border-[#E8DECB]/60 pt-2">
                        <span class="text-[9px] font-black uppercase tracking-widest text-[#A09585] block">Reported Target</span>
                        <span class="text-xs font-bold text-[#1E1915]" x-text="reportedName + (productName ? ' • ' + productName : '')"></span>
                    </div>
                    <div class="border-t border-[#E8DECB]/60 pt-2">
                        <span class="text-[9px] font-black uppercase tracking-widest text-[#A09585] block">Reason</span>
                        <span class="text-xs font-bold text-red-600" x-text="selectedReason"></span>
                    </div>
                    <div class="border-t border-[#E8DECB]/60 pt-2">
                        <span class="text-[9px] font-black uppercase tracking-widest text-[#A09585] block">Description</span>
                        <p class="text-xs text-[#1E1915] mt-0.5 leading-relaxed bg-white p-3 rounded-xl border border-[#E8DECB]" x-text="description"></p>
                    </div>
                    <div class="border-t border-[#E8DECB]/60 pt-2">
                        <span class="text-[9px] font-black uppercase tracking-widest text-[#A09585] block">Evidence Files</span>
                        <span class="text-xs font-bold text-[#1E1915]" x-text="evidenceFiles.length + ' file(s) attached'"></span>
                    </div>
                </div>

                <div class="p-3.5 rounded-2xl bg-[#FEF9EE] border border-[#F6DFA0] text-[11px] text-[#766C60] leading-relaxed">
                    ⚖️ <strong>Trust &amp; Safety Commitment:</strong> Submitting this report opens a formal investigation. All reports are evaluated fairly by platform moderators without automatic penalties.
                </div>
            </div>

            <!-- ══ STEP 5: Success Screen ════════════════════════════════ -->
            <div x-show="currentStep === 5" class="py-6 text-center space-y-4">
                <div class="w-16 h-16 rounded-3xl bg-[#F0F4EF] text-[#4A6741] border border-[#C5D9B8] flex items-center justify-center mx-auto shadow-xs">
                    <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                </div>
                <div>
                    <h3 class="font-serif text-xl font-bold text-[#1E1915]">Report Submitted Successfully</h3>
                    <p class="text-xs text-[#766C60] max-w-sm mx-auto mt-1 leading-relaxed">
                        Your report has been submitted successfully. Our Trust &amp; Safety team will review your concern and take appropriate action.
                    </p>
                </div>
                <div class="pt-2">
                    <button type="button" @click="isOpen = false" class="px-6 py-2.5 rounded-xl text-xs font-bold uppercase tracking-widest text-white shadow-md cursor-pointer" style="background: #1E1915;">
                        Close
                    </button>
                </div>
            </div>

        </div>

        <!-- Footer Action Buttons (Steps 1 to 4) -->
        <template x-if="currentStep <= 4">
            <div class="px-6 py-4 sm:px-8 sm:py-5 bg-[#FDF8EE] border-t border-[#E8DECB] flex items-center justify-between gap-3 shrink-0">
                <button type="button" 
                        @click="prevStep()"
                        class="px-5 py-3 rounded-xl text-xs font-bold uppercase tracking-widest cursor-pointer transition-all border border-[#E8DECB] text-[#766C60] bg-white hover:bg-[#FAF5EA]">
                    <span x-text="currentStep === 1 ? 'Cancel' : '← Back'"></span>
                </button>

                <button type="button" 
                        @click="nextStep()"
                        :disabled="isSubmitting || isUploading"
                        class="px-6 py-3 rounded-xl text-xs font-bold uppercase tracking-widest text-white shadow-md transition-all cursor-pointer flex items-center gap-2 disabled:opacity-50 disabled:cursor-not-allowed"
                        style="background: #1E1915;">
                    <span x-show="!isSubmitting" x-text="currentStep === 4 ? 'Submit Report ✓' : 'Continue →'"></span>
                    <span x-show="isSubmitting">Submitting...</span>
                </button>
            </div>
        </template>
    </div>
</div>

<script>
function trustSafetyReportModal() {
    return {
        isOpen: false,
        currentStep: 1,
        reportType: 'account', // 'account' or 'product'
        reportedId: null,
        reportedName: '',
        productId: null,
        productName: '',
        selectedReason: '',
        description: '',
        evidenceFiles: [],
        isUploading: false,
        isSubmitting: false,
        errorMessage: '',

        accountReasons: [
            "Fraud / Scam",
            "Fake or Misleading Seller Information",
            "Impersonation",
            "Suspicious Seller Activity",
            "Harassment or Abusive Behavior",
            "Off-platform Transaction",
            "Policy Violation",
            "Fake Reviews / Rating Manipulation",
            "Spam or Misleading Messages",
            "Prohibited Business Activity",
            "Other"
        ],

        productReasons: [
            "Counterfeit / Fake Item",
            "Misleading Product Details or Images",
            "Prohibited / Illegal Product",
            "Damaged or Defective Listing",
            "Inappropriate Content",
            "Pricing or Listing Manipulation",
            "Policy Violation",
            "Other"
        ],

        open(data) {
            this.isOpen = true;
            this.currentStep = 1;
            this.reportedId = data.reportedId;
            this.reportedName = data.reportedName || 'Seller Account';
            this.productId = data.productId || null;
            this.productName = data.productName || '';
            this.reportType = data.productId || data.reportType === 'product' ? 'product' : 'account';
            this.selectedReason = '';
            this.description = '';
            this.evidenceFiles = [];
            this.errorMessage = '';
            this.isUploading = false;
            this.isSubmitting = false;
        },

        stepTitle() {
            switch(this.currentStep) {
                case 1: return '1. Select Concern Reason';
                case 2: return '2. Provide Explanation';
                case 3: return '3. Attach Evidence';
                case 4: return '4. Review & Confirm';
                default: return 'Trust & Safety Report';
            }
        },

        reasonsList() {
            return this.reportType === 'product' ? this.productReasons : this.accountReasons;
        },

        prevStep() {
            this.errorMessage = '';
            if (this.currentStep === 1) {
                this.isOpen = false;
            } else {
                this.currentStep--;
            }
        },

        nextStep() {
            this.errorMessage = '';

            if (this.currentStep === 1) {
                if (!this.selectedReason) {
                    this.errorMessage = 'Please select a reason for your report to continue.';
                    return;
                }
                this.currentStep = 2;
            } else if (this.currentStep === 2) {
                if (this.description.trim().length < 10) {
                    this.errorMessage = 'Please enter at least 10 characters explaining your concern.';
                    return;
                }
                this.currentStep = 3;
            } else if (this.currentStep === 3) {
                this.currentStep = 4;
            } else if (this.currentStep === 4) {
                this.submitReport();
            }
        },

        async uploadEvidence(event) {
            const file = event.target.files[0];
            if (!file) return;

            if (file.size > 10 * 1024 * 1024) {
                this.errorMessage = 'File size exceeds 10MB limit. Please choose a smaller image.';
                event.target.value = '';
                return;
            }

            this.isUploading = true;
            this.errorMessage = '';

            const formData = new FormData();
            formData.append('image', file);

            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') 
                || document.querySelector('input[name="_token"]')?.value 
                || '';

            try {
                const res = await fetch('/api/v1/upload', {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': csrfToken,
                        'Accept': 'application/json'
                    },
                    body: formData
                });

                if (res.ok) {
                    const data = await res.json();
                    if (data.url) {
                        this.evidenceFiles.push(data.url);
                    }
                } else {
                    const err = await res.json().catch(() => ({}));
                    this.errorMessage = err.message || 'Failed to upload evidence image.';
                }
            } catch (e) {
                this.errorMessage = 'Network error while uploading evidence.';
            } finally {
                this.isUploading = false;
                event.target.value = '';
            }
        },

        removeEvidence(index) {
            this.evidenceFiles.splice(index, 1);
        },

        async submitReport() {
            this.isSubmitting = true;
            this.errorMessage = '';

            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') 
                || document.querySelector('input[name="_token"]')?.value 
                || '';

            try {
                const res = await fetch('/api/v1/reports', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': csrfToken
                    },
                    body: JSON.stringify({
                        reportedId: this.reportedId,
                        reportType: this.reportType,
                        productId: this.productId,
                        reason: this.selectedReason,
                        description: this.description,
                        evidence: this.evidenceFiles
                    })
                });

                const data = await res.json();

                if (res.ok && (data.status === 'success' || data.id)) {
                    this.currentStep = 5;
                } else {
                    this.errorMessage = data.message || 'Failed to submit report. Please try again.';
                }
            } catch (e) {
                this.errorMessage = 'A network error occurred. Please check your connection and try again.';
            } finally {
                this.isSubmitting = false;
            }
        }
    };
}
</script>
