@extends('layouts.seller')

@section('content')
<div class="max-w-5xl mx-auto space-y-6" x-data="policiesApp({
    initialCancellation: @js($user->cancellation_policy ?? ''),
    initialRefund: @js($user->refund_policy ?? ''),
    defaultCancellation: @js($user->getCancellationPolicy()),
    defaultRefund: @js($user->getRefundPolicy()),
    csrfToken: @js(csrf_token()),
    aiUrl: @js(route('seller.policies.ai'))
})">

    {{-- Breadcrumb & Header --}}
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 bg-white p-6 sm:p-8 rounded-3xl border border-gray-100 shadow-xs">
        <div>
            <div class="flex items-center gap-2 mb-2">
                <a href="{{ route('seller.profile') }}" class="text-[10px] font-bold text-gray-400 uppercase tracking-widest hover:text-[#C0420A] transition-colors flex items-center gap-1">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
                    Account Settings
                </a>
                <span class="text-gray-300">/</span>
                <span class="text-[10px] font-bold uppercase tracking-widest text-[#C0420A]">Shop Policies</span>
            </div>
            <h1 class="text-2xl sm:text-3xl font-serif font-black text-black">Shop Cancellation & Refund Policies</h1>
            <p class="text-xs text-gray-500 mt-1 max-w-2xl leading-relaxed">
                Define your shop's terms for cancellations, refunds, and returns. These policies are displayed prominently on your public artisan storefront and inside the buyer's checkout confirmation screen.
            </p>
        </div>

        <div class="flex items-center gap-2">
            <button type="button" @click="showPreviewModal = true" class="px-4 py-2.5 bg-gray-100 hover:bg-gray-200 text-gray-700 rounded-xl text-xs font-bold uppercase tracking-widest flex items-center gap-2 transition-all cursor-pointer">
                <svg class="w-4 h-4 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                <span>Buyer Preview</span>
            </button>
        </div>
    </div>

    @if(session('success'))
        <div class="p-4 bg-emerald-50 border border-emerald-200 rounded-2xl text-emerald-800 text-xs font-bold flex items-center gap-2.5 shadow-xs animate-fade-in">
            <svg class="w-5 h-5 text-emerald-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
            <span>{{ session('success') }}</span>
        </div>
    @endif

    {{-- Main Form --}}
    <form action="{{ route('seller.policies.update') }}" method="POST" class="space-y-6">
        @csrf
        @method('PUT')

        {{-- Cancellation Policy Card --}}
        <div class="bg-white rounded-3xl p-6 sm:p-8 border border-gray-100 shadow-sm space-y-4 relative overflow-hidden">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2 border-b border-gray-100 pb-4">
                <div class="flex items-center gap-2.5">
                    <div class="w-8 h-8 rounded-xl bg-amber-50 border border-amber-200/60 flex items-center justify-center text-amber-600 font-bold">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    </div>
                    <div>
                        <h2 class="text-sm font-black uppercase tracking-wider text-gray-900">Shop Cancellation Policy</h2>
                        <p class="text-[10px] text-gray-400">Rules regarding when an order can or cannot be cancelled by the buyer</p>
                    </div>
                </div>
                <div class="flex items-center gap-2">
                    <span class="text-[9px] font-bold px-2 py-0.5 rounded-full uppercase tracking-wider"
                          :class="cancellation_policy.trim() ? 'bg-emerald-50 text-emerald-700 border border-emerald-200' : 'bg-gray-100 text-gray-500'">
                        <span x-text="cancellation_policy.trim() ? 'Custom Policy' : 'Using Artisan Default'"></span>
                    </span>
                </div>
            </div>

            {{-- AI Actions Bar --}}
            <div class="bg-linear-to-r from-amber-50/70 via-[#FDF9F4] to-orange-50/70 p-3.5 rounded-2xl border border-amber-200/50 flex flex-wrap items-center justify-between gap-2">
                <div class="flex items-center gap-1.5 text-xs font-bold text-amber-950">
                    <span class="inline-block animate-pulse text-amber-600">✨</span>
                    <span>AI Policy Assistant:</span>
                </div>
                <div class="flex flex-wrap items-center gap-2">
                    {{-- Tone dropdown --}}
                    <select x-model="cancellationTone" class="bg-white border border-amber-200 text-gray-700 text-[11px] font-bold rounded-lg px-2 py-1.5 outline-none focus:ring-2 focus:ring-[#C0420A]/20 cursor-pointer">
                        <option value="standard">Tone: Balanced Standard</option>
                        <option value="strict">Tone: Strict (Made-to-Order)</option>
                        <option value="flexible">Tone: Flexible & Friendly</option>
                    </select>

                    <button type="button" @click="runAi('cancellation', 'generate')" :disabled="loadingCancellation" class="px-3 py-1.5 bg-[#C0420A] text-white hover:bg-[#A33622] rounded-lg text-[10px] font-bold uppercase tracking-wider transition-all flex items-center gap-1.5 cursor-pointer shadow-xs disabled:opacity-50">
                        <svg x-show="!loadingCancellation" class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                        <svg x-show="loadingCancellation" class="w-3.5 h-3.5 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                        <span>Generate with AI</span>
                    </button>

                    <button type="button" @click="runAi('cancellation', 'improve')" :disabled="loadingCancellation || !cancellation_policy.trim()" class="px-3 py-1.5 bg-white border border-amber-200 text-gray-800 hover:border-[#C0420A] rounded-lg text-[10px] font-bold uppercase tracking-wider transition-all flex items-center gap-1 cursor-pointer disabled:opacity-40 disabled:cursor-not-allowed">
                        <span>🪄 Improve Draft</span>
                    </button>

                    <button type="button" @click="runAi('cancellation', 'translate')" :disabled="loadingCancellation || !cancellation_policy.trim()" class="px-3 py-1.5 bg-white border border-amber-200 text-gray-800 hover:border-[#C0420A] rounded-lg text-[10px] font-bold uppercase tracking-wider transition-all flex items-center gap-1 cursor-pointer disabled:opacity-40 disabled:cursor-not-allowed" title="Translate Tagalog/Taglish input into professional English">
                        <span>🌐 Translate Tagalog ➔ EN</span>
                    </button>
                </div>
            </div>

            {{-- Textarea Input --}}
            <div class="space-y-1.5">
                <div class="flex items-center justify-between text-[11px] font-bold text-gray-500">
                    <label class="uppercase tracking-wider">Policy Statement (English)</label>
                    <span :class="cancellation_policy.length > 1800 ? 'text-amber-600' : 'text-gray-400'" x-text="`${cancellation_policy.length} / 2000 characters`"></span>
                </div>
                <textarea name="cancellation_policy" 
                          rows="4" 
                          x-model="cancellation_policy"
                          maxlength="2000"
                          placeholder="e.g. Cancellation is strictly allowed only before order processing and payment verification. Once handcrafted tailoring and custom embroidery have commenced, cancellations can no longer be accepted."
                          class="w-full px-4 py-3 border border-gray-200 rounded-2xl text-xs sm:text-sm font-medium leading-relaxed outline-none focus:border-[#C0420A] focus:ring-4 focus:ring-[#C0420A]/10 transition-all shadow-xs bg-white"></textarea>
            </div>

            {{-- Preset Quick Fill Buttons --}}
            <div class="pt-2 border-t border-gray-100 flex flex-wrap items-center justify-between gap-2">
                <div class="flex flex-wrap items-center gap-1.5 text-[10px] font-bold text-gray-400">
                    <span>Quick Presets:</span>
                    <button type="button" @click="setPreset('cancellation', 'standard')" class="px-2 py-1 bg-gray-100 hover:bg-gray-200 text-gray-700 rounded-md transition-colors cursor-pointer">Standard Artisan</button>
                    <button type="button" @click="setPreset('cancellation', 'strict')" class="px-2 py-1 bg-gray-100 hover:bg-gray-200 text-gray-700 rounded-md transition-colors cursor-pointer">Strict Made-to-Order</button>
                    <button type="button" @click="setPreset('cancellation', 'flexible')" class="px-2 py-1 bg-gray-100 hover:bg-gray-200 text-gray-700 rounded-md transition-colors cursor-pointer">12-Hour Grace Period</button>
                </div>
                <button type="button" @click="cancellation_policy = ''" x-show="cancellation_policy" class="text-[10px] font-bold text-gray-400 hover:text-red-500 transition-colors cursor-pointer">
                    Clear Text
                </button>
            </div>
        </div>

        {{-- Refund & Return Policy Card --}}
        <div class="bg-white rounded-3xl p-6 sm:p-8 border border-gray-100 shadow-sm space-y-4 relative overflow-hidden">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2 border-b border-gray-100 pb-4">
                <div class="flex items-center gap-2.5">
                    <div class="w-8 h-8 rounded-xl bg-blue-50 border border-blue-200/60 flex items-center justify-center text-blue-600 font-bold">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    </div>
                    <div>
                        <h2 class="text-sm font-black uppercase tracking-wider text-gray-900">Shop Refund & Return Policy</h2>
                        <p class="text-[10px] text-gray-400">Rules regarding returns, exchanges, sizing adjustments, and defects</p>
                    </div>
                </div>
                <div class="flex items-center gap-2">
                    <span class="text-[9px] font-bold px-2 py-0.5 rounded-full uppercase tracking-wider"
                          :class="refund_policy.trim() ? 'bg-emerald-50 text-emerald-700 border border-emerald-200' : 'bg-gray-100 text-gray-500'">
                        <span x-text="refund_policy.trim() ? 'Custom Policy' : 'Using Artisan Default'"></span>
                    </span>
                </div>
            </div>

            {{-- AI Actions Bar --}}
            <div class="bg-linear-to-r from-blue-50/70 via-[#FDF9F4] to-cyan-50/70 p-3.5 rounded-2xl border border-blue-200/50 flex flex-wrap items-center justify-between gap-2">
                <div class="flex items-center gap-1.5 text-xs font-bold text-blue-950">
                    <span class="inline-block animate-pulse text-blue-600">✨</span>
                    <span>AI Policy Assistant:</span>
                </div>
                <div class="flex flex-wrap items-center gap-2">
                    {{-- Tone dropdown --}}
                    <select x-model="refundTone" class="bg-white border border-blue-200 text-gray-700 text-[11px] font-bold rounded-lg px-2 py-1.5 outline-none focus:ring-2 focus:ring-blue-500/20 cursor-pointer">
                        <option value="standard">Tone: Balanced Standard</option>
                        <option value="strict">Tone: Strict (No Remorse Returns)</option>
                        <option value="flexible">Tone: Flexible & Exchanges</option>
                    </select>

                    <button type="button" @click="runAi('refund', 'generate')" :disabled="loadingRefund" class="px-3 py-1.5 bg-blue-600 text-white hover:bg-blue-700 rounded-lg text-[10px] font-bold uppercase tracking-wider transition-all flex items-center gap-1.5 cursor-pointer shadow-xs disabled:opacity-50">
                        <svg x-show="!loadingRefund" class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                        <svg x-show="loadingRefund" class="w-3.5 h-3.5 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                        <span>Generate with AI</span>
                    </button>

                    <button type="button" @click="runAi('refund', 'improve')" :disabled="loadingRefund || !refund_policy.trim()" class="px-3 py-1.5 bg-white border border-blue-200 text-gray-800 hover:border-blue-500 rounded-lg text-[10px] font-bold uppercase tracking-wider transition-all flex items-center gap-1 cursor-pointer disabled:opacity-40 disabled:cursor-not-allowed">
                        <span>🪄 Improve Draft</span>
                    </button>

                    <button type="button" @click="runAi('refund', 'translate')" :disabled="loadingRefund || !refund_policy.trim()" class="px-3 py-1.5 bg-white border border-blue-200 text-gray-800 hover:border-blue-500 rounded-lg text-[10px] font-bold uppercase tracking-wider transition-all flex items-center gap-1 cursor-pointer disabled:opacity-40 disabled:cursor-not-allowed" title="Translate Tagalog/Taglish input into professional English">
                        <span>🌐 Translate Tagalog ➔ EN</span>
                    </button>
                </div>
            </div>

            {{-- Textarea Input --}}
            <div class="space-y-1.5">
                <div class="flex items-center justify-between text-[11px] font-bold text-gray-500">
                    <label class="uppercase tracking-wider">Policy Statement (English)</label>
                    <span :class="refund_policy.length > 1800 ? 'text-amber-600' : 'text-gray-400'" x-text="`${refund_policy.length} / 2000 characters`"></span>
                </div>
                <textarea name="refund_policy" 
                          rows="4" 
                          x-model="refund_policy"
                          maxlength="2000"
                          placeholder="e.g. Custom size garments crafted to provided measurements are final sale. Damaged or defective items upon delivery must be reported within 48 hours with unboxing video proof to initiate a replacement or refund."
                          class="w-full px-4 py-3 border border-gray-200 rounded-2xl text-xs sm:text-sm font-medium leading-relaxed outline-none focus:border-blue-600 focus:ring-4 focus:ring-blue-600/10 transition-all shadow-xs bg-white"></textarea>
            </div>

            {{-- Preset Quick Fill Buttons --}}
            <div class="pt-2 border-t border-gray-100 flex flex-wrap items-center justify-between gap-2">
                <div class="flex flex-wrap items-center gap-1.5 text-[10px] font-bold text-gray-400">
                    <span>Quick Presets:</span>
                    <button type="button" @click="setPreset('refund', 'standard')" class="px-2 py-1 bg-gray-100 hover:bg-gray-200 text-gray-700 rounded-md transition-colors cursor-pointer">Standard Artisan</button>
                    <button type="button" @click="setPreset('refund', 'strict')" class="px-2 py-1 bg-gray-100 hover:bg-gray-200 text-gray-700 rounded-md transition-colors cursor-pointer">Strict Tailor-Made</button>
                    <button type="button" @click="setPreset('refund', 'flexible')" class="px-2 py-1 bg-gray-100 hover:bg-gray-200 text-gray-700 rounded-md transition-colors cursor-pointer">7-Day Sizing Exchange</button>
                </div>
                <button type="button" @click="refund_policy = ''" x-show="refund_policy" class="text-[10px] font-bold text-gray-400 hover:text-red-500 transition-colors cursor-pointer">
                    Clear Text
                </button>
            </div>
        </div>

        {{-- Form Actions Bar --}}
        <div class="flex flex-col sm:flex-row items-center justify-between gap-4 pt-2">
            <a href="{{ route('seller.profile') }}" class="text-xs font-bold text-gray-500 hover:text-gray-800 uppercase tracking-widest transition-colors flex items-center gap-1.5">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
                Return to Account Settings
            </a>

            <div class="flex items-center gap-3 w-full sm:w-auto">
                <button type="button" @click="revertAll()" x-show="hasChanges()" class="px-5 py-3 rounded-xl bg-gray-100 text-gray-700 hover:bg-gray-200 text-xs font-black uppercase tracking-widest transition-all cursor-pointer">
                    Revert
                </button>
                <button type="submit" 
                        :disabled="!hasChanges()"
                        :class="hasChanges() ? 'bg-[#C0420A] text-white hover:bg-black cursor-pointer shadow-md' : 'bg-gray-200 text-gray-400 cursor-not-allowed opacity-75'"
                        class="flex-1 sm:flex-none px-8 py-3.5 rounded-xl text-xs font-black uppercase tracking-widest transition-all flex items-center justify-center gap-2 shadow-sm">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                    <span>Save Shop Policies</span>
                </button>
            </div>
        </div>
    </form>

    {{-- Live Buyer Preview Modal --}}
    <div x-show="showPreviewModal" class="fixed inset-0 z-110 flex items-center justify-center p-4" style="display: none;" x-cloak>
        <div class="absolute inset-0 bg-black/60 backdrop-blur-sm" @click="showPreviewModal = false"></div>
        <div @click.away="showPreviewModal = false" class="relative bg-white w-full max-w-xl rounded-3xl shadow-2xl p-6 sm:p-8 space-y-5 max-h-[90vh] overflow-y-auto" x-transition>
            <div class="flex items-center justify-between border-b border-gray-100 pb-3">
                <div class="flex items-center gap-2">
                    <div class="w-3 h-3 rounded-full bg-[#C0420A]"></div>
                    <h3 class="font-serif text-lg sm:text-xl font-bold text-black">Buyer Preview: How Customers See Your Policies</h3>
                </div>
                <button @click="showPreviewModal = false" class="text-gray-400 hover:text-gray-600 transition-colors p-1 cursor-pointer">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>

            <div class="space-y-4 text-left">
                <div class="p-3 bg-amber-50 rounded-xl border border-amber-200 text-xs text-amber-900">
                    <strong>Preview Mode:</strong> This is the exact view buyers see when they view your policies on your public store or when checking out an order containing your products.
                </div>

                {{-- Cancellation Policy Preview --}}
                <div class="p-4 bg-stone-50 rounded-2xl border border-stone-200 space-y-1.5">
                    <div class="flex items-center gap-1.5 text-xs font-bold text-amber-800 uppercase tracking-wider">
                        <svg class="w-4 h-4 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        <span>Cancellation Policy</span>
                    </div>
                    <p class="text-xs text-gray-700 leading-relaxed font-medium" x-text="cancellation_policy.trim() || defaultCancellation"></p>
                </div>

                {{-- Refund & Return Policy Preview --}}
                <div class="p-4 bg-stone-50 rounded-2xl border border-stone-200 space-y-1.5">
                    <div class="flex items-center gap-1.5 text-xs font-bold text-blue-800 uppercase tracking-wider">
                        <svg class="w-4 h-4 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        <span>Refund & Return Policy</span>
                    </div>
                    <p class="text-xs text-gray-700 leading-relaxed font-medium" x-text="refund_policy.trim() || defaultRefund"></p>
                </div>
            </div>

            <div class="pt-2 text-right">
                <button type="button" @click="showPreviewModal = false" class="w-full py-3 rounded-xl bg-gray-900 text-white text-xs font-bold uppercase tracking-widest hover:bg-[#C0420A] transition-all cursor-pointer">
                    Close Preview
                </button>
            </div>
        </div>
    </div>
</div>

<script>
function policiesApp(config) {
    return {
        initialCancellation: config.initialCancellation || '',
        initialRefund: config.initialRefund || '',
        defaultCancellation: config.defaultCancellation,
        defaultRefund: config.defaultRefund,
        cancellation_policy: config.initialCancellation || '',
        refund_policy: config.initialRefund || '',
        cancellationTone: 'standard',
        refundTone: 'standard',
        loadingCancellation: false,
        loadingRefund: false,
        showPreviewModal: false,
        csrfToken: config.csrfToken,
        aiUrl: config.aiUrl,

        hasChanges() {
            return this.cancellation_policy !== this.initialCancellation ||
                   this.refund_policy !== this.initialRefund;
        },

        revertAll() {
            this.cancellation_policy = this.initialCancellation;
            this.refund_policy = this.initialRefund;
        },

        setPreset(type, preset) {
            const presets = {
                cancellation: {
                    standard: "Cancellation requests must be submitted prior to payment verification and order processing. Once handcrafted tailoring has begun, cancellations may no longer be accepted. Please ensure all sizing and delivery details are accurate before completing payment.",
                    strict: "All orders enter production immediately upon payment confirmation. Cancellations are strictly not accepted once tailoring, embroidery, or fabric cutting has commenced. Please review measurements and order specifications carefully prior to checkout.",
                    flexible: "You may cancel your order free of charge within 12 hours of purchase. Once your custom garment has entered the active tailoring stage, cancellation requests will be reviewed on a case-by-case basis."
                },
                refund: {
                    standard: "Refund and return requests are subject to shop evaluation. Custom-sized garments crafted to provided measurements are final sale. Damaged or defective items upon delivery must be reported within 48 hours with unboxing proof to initiate a return or adjustment.",
                    strict: "Custom tailored barongs and bespoke items are non-refundable and final sale. Replacement or store credit is only granted for verified manufacturing defects reported within 48 hours of delivery with continuous unboxing video proof.",
                    flexible: "We want you to love your handcrafted garment. If your item does not fit or has defects, you may request an exchange or adjustment within 7 days of delivery, provided the item is unwashed and tags remain intact."
                }
            };

            if (type === 'cancellation') {
                this.cancellation_policy = presets.cancellation[preset] || '';
            } else {
                this.refund_policy = presets.refund[preset] || '';
            }
        },

        async runAi(type, action) {
            const isCancellation = type === 'cancellation';
            if (isCancellation) {
                this.loadingCancellation = true;
            } else {
                this.loadingRefund = true;
            }

            const draft = isCancellation ? this.cancellation_policy : this.refund_policy;
            const tone = isCancellation ? this.cancellationTone : this.refundTone;

            try {
                const res = await fetch(this.aiUrl, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': this.csrfToken
                    },
                    body: JSON.stringify({
                        type: type,
                        action: action,
                        draft: draft,
                        tone: tone
                    })
                });

                const data = await res.json();
                if (data.success && data.text) {
                    if (isCancellation) {
                        this.cancellation_policy = data.text;
                    } else {
                        this.refund_policy = data.text;
                    }
                    if (window.Alpine && Alpine.store('toast')) {
                        Alpine.store('toast').show('✨ AI generated and refined your policy successfully!', 'success');
                    }
                } else {
                    alert(data.message || 'AI request could not be completed. Please try again.');
                }
            } catch (err) {
                console.error(err);
                alert('Connection error with AI service. Please check your network connection.');
            } finally {
                if (isCancellation) {
                    this.loadingCancellation = false;
                } else {
                    this.loadingRefund = false;
                }
            }
        }
    };
}
</script>
@endsection
