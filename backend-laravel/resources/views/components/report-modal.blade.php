<div x-data="reportModal()" 
    @open-report.window="open($event.detail)"
    x-show="isOpen" 
    class="fixed inset-0 z-1000 flex items-center justify-center p-4" 
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
    <div class="relative w-full max-w-lg bg-white rounded-4xl shadow-2xl overflow-hidden artisan-card p-0 border border-gray-100">
        <template x-if="success">
            <div class="p-12 text-center space-y-6">
                <div class="w-20 h-20 bg-green-50 text-green-600 rounded-3xl flex items-center justify-center mx-auto ring-8 ring-green-50/50">
                    <svg class="w-10 h-10" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                </div>
                <div>
                    <h3 class="font-serif text-2xl font-bold text-black uppercase tracking-tight">Report <span class="text-[#C0420A] italic lowercase">Submitted</span></h3>
                    <p class="text-[10px] font-black text-gray-400 uppercase tracking-widest mt-2">Our trust & safety team will review this shortly.</p>
                </div>
            </div>
        </template>

        <template x-if="!success">
            <div class="p-8 sm:p-10 space-y-8">
                <div class="flex justify-between items-start">
                    <div>
                        <div class="text-[10px] font-bold text-red-600 uppercase tracking-widest mb-1">Integrity Violation</div>
                        <h3 class="font-serif text-2xl font-bold text-black tracking-tighter uppercase">
                            Report <span class="text-[#C0420A] italic lowercase" x-text="type === 'CustomerReportingSeller' ? 'Store' : 'Customer'"></span>
                        </h3>
                        <p class="text-[9px] font-black text-gray-400 opacity-50 uppercase tracking-widest mt-1" x-text="'Reporting: ' + reportedName"></p>
                    </div>
                    <button @click="isOpen = false" class="p-2 hover:bg-gray-50 rounded-xl transition-all">
                        <svg class="w-6 h-6 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>
                </div>

                <form @submit.prevent="submit" class="space-y-6">
                    <div class="space-y-2">
                        <label class="text-[9px] font-black uppercase tracking-widest text-gray-400 ml-1">Reason for Report</label>
                        <select x-model="reason" required class="w-full px-5 py-4 bg-gray-50/30 border border-gray-100 focus:border-[#C0420A] rounded-2xl outline-none text-[11px] font-bold transition-all shadow-sm">
                            <option value="">Select a reason...</option>
                            <template x-for="r in reasons">
                                <option :value="r" x-text="r"></option>
                            </template>
                        </select>
                    </div>

                    <div class="space-y-2">
                        <div class="flex items-center justify-between ml-1">
                            <label class="text-[9px] font-black uppercase tracking-widest text-gray-400">Detailed Description</label>
                            <span class="text-[9px] font-bold tabular-nums" :class="description.length >= 50 ? 'text-green-600' : 'text-gray-400'" x-text="description.length + '/50 min'"></span>
                        </div>
                        <textarea 
                            x-model="description" 
                            required 
                            rows="4" 
                            class="w-full px-5 py-4 bg-gray-50/30 border focus:border-[#C0420A] rounded-2xl outline-none text-[11px] font-bold transition-all shadow-sm resize-none" 
                            :class="description.length > 0 && description.length < 50 ? 'border-amber-400' : 'border-gray-100'"
                            placeholder="Please explain the situation in detail (minimum 50 characters)..."
                        ></textarea>
                    </div>

                    <div class="space-y-2">
                        <label class="text-[9px] font-black uppercase tracking-widest text-gray-400 ml-1">Evidence (Optional Image)</label>
                        <div class="flex items-center gap-3">
                            <label class="cursor-pointer flex items-center justify-center gap-2 px-5 py-3 border border-dashed border-gray-200 hover:border-[#C0420A] rounded-2xl text-[10px] font-bold text-gray-500 hover:text-[#C0420A] bg-gray-50/20 transition-all grow">
                                <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                                <span class="truncate" x-text="imageFileName || 'Upload evidence image...'"></span>
                                <input type="file" accept="image/*" @change="uploadImage($event)" class="hidden">
                            </label>
                            <template x-if="evidenceUrl">
                                <div class="relative w-12 h-12 rounded-xl overflow-hidden border border-gray-200 shrink-0 shadow-sm">
                                    <img :src="evidenceUrl" class="w-full h-full object-cover">
                                    <button type="button" @click="evidenceUrl = ''; imageFileName = ''" class="absolute inset-0 bg-black/60 flex items-center justify-center opacity-0 hover:opacity-100 transition-opacity text-white">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                                    </button>
                                </div>
                            </template>
                        </div>
                        <template x-if="isUploading">
                            <div class="text-[9px] text-[#C0420A] animate-pulse font-bold ml-1">Uploading image...</div>
                        </template>
                    </div>

                    <div class="flex gap-4 pt-4">
                        <button type="button" @click="isOpen = false" class="flex-1 py-4 bg-gray-50 text-black text-[11px] font-black uppercase tracking-[0.2em] rounded-2xl hover:bg-gray-100 transition-all">
                            Cancel
                        </button>
                        <button 
                            type="submit" 
                            :disabled="isSubmitting || isUploading || description.length < 50 || !reason" 
                            class="flex-2 py-4 bg-[#C0420A] text-white text-[11px] font-black uppercase tracking-[0.2em] rounded-2xl shadow-xl shadow-red-100 hover:bg-black transition-all active:scale-[0.98] disabled:opacity-50 disabled:pointer-events-none"
                        >
                            <span x-text="isSubmitting ? 'Submitting...' : 'Submit Report'"></span>
                        </button>
                    </div>
                </form>
            </div>
        </template>
    </div>
</div>

<script>
function reportModal() {
    return {
        isOpen: false,
        reportedId: null,
        reportedName: '',
        type: 'CustomerReportingSeller',
        reason: '',
        description: '',
        evidenceUrl: '',
        imageFileName: '',
        isUploading: false,
        isSubmitting: false,
        success: false,
        reasons: [],
        open(data) {
            this.isOpen = true;
            this.reportedId = data.reportedId;
            this.reportedName = data.reportedName;
            this.type = data.type || 'CustomerReportingSeller';
            this.reasons = this.type === 'CustomerReportingSeller' 
                ? ["Counterfeit Items", "Misleading Information", "Fraud / Scam", "Prohibited Items", "Abusive Behavior", "Policy Violation", "Other"]
                : ["Fraudulent Order", "Abusive Behavior", "Refund Abuse", "Unpaid COD Spam", "Fake Payment Proof", "Other"];
            this.success = false;
            this.reason = '';
            this.description = '';
            this.evidenceUrl = '';
            this.imageFileName = '';
            this.isUploading = false;
        },
        async uploadImage(event) {
            const file = event.target.files[0];
            if (!file) return;
            this.imageFileName = file.name;
            this.isUploading = true;
            
            const formData = new FormData();
            formData.append('image', file);
            
            try {
                const res = await fetch('/api/v1/upload', {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || ''
                    },
                    body: formData
                });
                if (res.ok) {
                    const data = await res.json();
                    this.evidenceUrl = data.url;
                } else {
                    alert('Failed to upload image. Please try again.');
                    this.imageFileName = '';
                }
            } catch (e) {
                console.error(e);
                alert('Error uploading image.');
                this.imageFileName = '';
            }
            this.isUploading = false;
        },
        async submit() {
            this.isSubmitting = true;
            try {
                const res = await fetch('/api/v1/reports', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || ''
                    },
                    body: JSON.stringify({
                        reportedId: this.reportedId,
                        type: this.type,
                        reason: this.reason,
                        description: this.description,
                        evidence: this.evidenceUrl || null
                    })
                });
                if (res.ok) {
                    this.success = true;
                    setTimeout(() => { this.isOpen = false; }, 3000);
                } else {
                    const err = await res.json();
                    alert(err.message || 'Failed to submit report');
                }
            } catch (e) { 
                console.error(e);
                alert('An error occurred. Please try again.');
            }
            this.isSubmitting = false;
        }
    }
}
</script>
