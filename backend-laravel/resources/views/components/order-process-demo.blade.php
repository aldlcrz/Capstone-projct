{{-- Interactive Ordering Process Demo Simulator --}}
<div x-data="orderProcessDemoEngine()"
     @open-order-demo.window="openDemo()"
     x-cloak
     x-show="isOpen"
     class="fixed inset-0 z-10000 overflow-y-auto"
     style="display: none;"
     aria-labelledby="order-demo-title"
     role="dialog"
     aria-modal="true">

    {{-- Backdrop --}}
    <div class="fixed inset-0 bg-black/75 backdrop-blur-sm transition-opacity"
         x-show="isOpen"
         x-transition:enter="ease-out duration-300"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="ease-in duration-200"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         @click="closeDemo()"></div>

    <div class="flex min-h-full items-center justify-center p-3 sm:p-4 text-center">
        <div class="relative w-full max-w-2xl transform overflow-hidden rounded-3xl bg-[#FFFCF7] text-left align-middle shadow-2xl transition-all border border-[#E8DECB]"
             x-show="isOpen"
             x-transition:enter="ease-out duration-300"
             x-transition:enter-start="opacity-0 scale-95 translate-y-4"
             x-transition:enter-end="opacity-100 scale-100 translate-y-0"
             x-transition:leave="ease-in duration-200"
             x-transition:leave-start="opacity-100 scale-100 translate-y-0"
             x-transition:leave-end="opacity-0 scale-95 translate-y-4"
             @click.stop>

            {{-- Top Banner: Demo Mode Indicator --}}
            <div class="bg-[#1E1915] text-[#FFFCF7] px-5 py-3 flex items-center justify-between border-b border-[#3B3228]">
                <div class="flex items-center gap-2.5">
                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-extrabold uppercase tracking-wider bg-[#C49520] text-[#1E1915]">
                        Interactive Demo
                    </span>
                    <span class="text-xs font-medium text-[#E8DECB]/90 hidden sm:inline">
                        Experience the complete ordering lifecycle (100% risk-free)
                    </span>
                </div>
                <button type="button"
                        @click="closeDemo()"
                        class="text-gray-400 hover:text-white text-sm font-bold w-7 h-7 rounded-full flex items-center justify-center hover:bg-white/10 transition-colors"
                        title="Close Demo">
                    ✕
                </button>
            </div>

            {{-- Step Navigation Stepper --}}
            <div class="bg-[#FDF8EE] px-4 sm:px-6 py-3 border-b border-[#E8DECB] overflow-x-auto no-scrollbar">
                <div class="flex items-center justify-between min-w-[360px] gap-2">
                    <template x-for="(s, index) in steps" :key="index">
                        <button type="button"
                                @click="currentStep = index"
                                class="flex items-center gap-2 text-left transition-all group cursor-pointer"
                                :class="{ 'opacity-100': currentStep === index, 'opacity-60 hover:opacity-90': currentStep !== index }">
                            <span class="w-6 h-6 rounded-full flex items-center justify-center text-[10px] font-extrabold shrink-0 transition-colors"
                                  :class="currentStep === index ? 'bg-[#C49520] text-white shadow-sm' : (currentStep > index ? 'bg-[#1E1915] text-white' : 'bg-[#E8DECB] text-[#766C60]')">
                                <span x-show="currentStep <= index" x-text="index + 1"></span>
                                <span x-show="currentStep > index">✓</span>
                            </span>
                            <div class="min-w-0">
                                <div class="text-[9px] uppercase tracking-wider font-bold text-[#A89887]" x-text="'Step ' + (index + 1)"></div>
                                <div class="text-xs font-bold text-[#1E1915] truncate" x-text="s.shortTitle"></div>
                            </div>
                        </button>
                    </template>
                </div>
            </div>

            {{-- Step Content Area --}}
            <div class="p-5 sm:p-7 max-h-[70vh] overflow-y-auto">

                {{-- STEP 1: Product Customization & Sizing --}}
                <div x-show="currentStep === 0" x-transition class="space-y-5">
                    <div class="flex items-start justify-between gap-3">
                        <div>
                            <span class="text-[10px] font-bold uppercase tracking-widest text-[#C49520]">Step 1: Choose Fit & Style</span>
                            <h3 class="font-serif text-xl sm:text-2xl font-bold text-[#1E1915]">Heritage Piña-Seda Barong Tagalog</h3>
                            <p class="text-xs text-[#766C60] mt-1">Sold by <strong class="text-[#1E1915]">Lumban Master Tailors Guild</strong> (Verified Artisan)</p>
                        </div>
                        <div class="text-right">
                            <div class="text-xl font-extrabold text-[#1E1915]">₱4,850.00</div>
                            <span class="inline-block text-[9px] font-bold text-emerald-700 bg-emerald-50 px-2 py-0.5 rounded border border-emerald-200">Free Shipping</span>
                        </div>
                    </div>

                    {{-- Fabric Selector --}}
                    <div class="space-y-2">
                        <label class="text-xs font-bold text-[#1E1915] flex items-center justify-between">
                            <span>Select Fabric Type:</span>
                            <span class="text-[11px] font-medium text-[#C49520]" x-text="selectedFabric"></span>
                        </label>
                        <div class="grid grid-cols-3 gap-2">
                            <template x-for="f in fabrics" :key="f.name">
                                <button type="button"
                                        @click="selectedFabric = f.name"
                                        class="p-3 rounded-2xl border text-left transition-all"
                                        :class="selectedFabric === f.name ? 'border-[#C49520] bg-[#FFF9ED] ring-1 ring-[#C49520]' : 'border-[#E8DECB] bg-white hover:border-[#C49520]/50'">
                                    <div class="text-xs font-bold text-[#1E1915]" x-text="f.name"></div>
                                    <div class="text-[10px] text-[#766C60]" x-text="f.desc"></div>
                                </button>
                            </template>
                        </div>
                    </div>

                    {{-- Size Selector (Standard vs Made-to-Measure) --}}
                    <div class="space-y-2">
                        <div class="flex items-center justify-between">
                            <label class="text-xs font-bold text-[#1E1915]">Select Sizing:</label>
                            <button type="button" @click="isCustomSize = !isCustomSize" class="text-[11px] font-bold text-[#C49520] underline hover:text-[#1E1915]">
                                <span x-text="isCustomSize ? 'Switch to Standard Sizes' : 'Request Made-to-Measure (+₱0)'"></span>
                            </button>
                        </div>

                        {{-- Standard Size Pills --}}
                        <div x-show="!isCustomSize" class="flex flex-wrap gap-2">
                            <template x-for="sz in ['S', 'M', 'L', 'XL', '2XL', '3XL']" :key="sz">
                                <button type="button"
                                        @click="selectedSize = sz"
                                        class="w-12 h-10 rounded-xl border text-xs font-bold transition-all flex items-center justify-center"
                                        :class="selectedSize === sz ? 'bg-[#1E1915] text-white border-[#1E1915] shadow-sm' : 'bg-white text-[#1E1915] border-[#E8DECB] hover:border-[#C49520]'">
                                    <span x-text="sz"></span>
                                </button>
                            </template>
                        </div>

                        {{-- Made to Measure Input Preview --}}
                        <div x-show="isCustomSize" class="p-3.5 rounded-2xl bg-white border border-[#E8DECB] space-y-2.5">
                            <div class="text-xs font-bold text-[#1E1915] flex items-center gap-1.5">
                                <span>📐 Custom Tailoring Measurements (Inches)</span>
                            </div>
                            <div class="grid grid-cols-3 gap-2">
                                <div>
                                    <label class="text-[10px] text-[#766C60]">Chest</label>
                                    <input type="text" value="38 in" readonly class="w-full text-xs font-bold bg-[#FDF8EE] border border-[#E8DECB] rounded-lg px-2.5 py-1.5 text-center">
                                </div>
                                <div>
                                    <label class="text-[10px] text-[#766C60]">Shoulder</label>
                                    <input type="text" value="17.5 in" readonly class="w-full text-xs font-bold bg-[#FDF8EE] border border-[#E8DECB] rounded-lg px-2.5 py-1.5 text-center">
                                </div>
                                <div>
                                    <label class="text-[10px] text-[#766C60]">Sleeve Length</label>
                                    <input type="text" value="24.5 in" readonly class="w-full text-xs font-bold bg-[#FDF8EE] border border-[#E8DECB] rounded-lg px-2.5 py-1.5 text-center">
                                </div>
                            </div>
                            <p class="text-[10px] text-gray-500 italic">Custom measurements will be sent directly to the artisan's tailoring workshop.</p>
                        </div>
                    </div>

                    <div class="pt-3 border-t border-[#E8DECB] flex items-center justify-between">
                        <div class="text-xs text-[#766C60]">
                            Selected: <strong class="text-[#1E1915]" x-text="isCustomSize ? 'Custom Made-to-Measure (' + selectedFabric + ')' : 'Size ' + selectedSize + ' (' + selectedFabric + ')'"></strong>
                        </div>
                        <button type="button"
                                @click="goToStep(1)"
                                class="px-5 py-2.5 rounded-full bg-[#1E1915] text-white text-xs font-bold hover:bg-[#C49520] transition-all shadow-md flex items-center gap-1.5">
                            <span>Add to Cart & Next →</span>
                        </button>
                    </div>
                </div>

                {{-- STEP 2: Cart Review --}}
                <div x-show="currentStep === 1" x-transition class="space-y-5">
                    <div class="flex items-start justify-between">
                        <div>
                            <span class="text-[10px] font-bold uppercase tracking-widest text-[#C49520]">Step 2: Review Shopping Cart</span>
                            <h3 class="font-serif text-xl sm:text-2xl font-bold text-[#1E1915]">Your Shopping Bag</h3>
                            <p class="text-xs text-[#766C60] mt-1">Review items grouped by verified artisan shops.</p>
                        </div>
                    </div>

                    {{-- Cart Shop Card --}}
                    <div class="p-4 rounded-2xl bg-white border border-[#E8DECB] space-y-3">
                        <div class="flex items-center justify-between border-b border-gray-100 pb-2">
                            <div class="flex items-center gap-2">
                                <span class="w-2 h-2 rounded-full bg-emerald-500"></span>
                                <span class="text-xs font-bold text-[#1E1915]">Lumban Master Tailors Guild</span>
                            </div>
                            <span class="text-[10px] font-bold uppercase tracking-wider text-[#C49520]">Laguna, PH</span>
                        </div>

                        <div class="flex items-center gap-3">
                            <div class="w-14 h-14 rounded-xl bg-[#FDF8EE] border border-[#E8DECB] flex items-center justify-center text-2xl shrink-0">
                                👔
                            </div>
                            <div class="flex-1 min-w-0">
                                <h4 class="text-xs font-bold text-[#1E1915] truncate">Heritage Piña-Seda Barong Tagalog</h4>
                                <div class="text-[11px] text-[#766C60]" x-text="(isCustomSize ? 'Custom Fit' : 'Size: ' + selectedSize) + ' • ' + selectedFabric"></div>
                                <div class="text-xs font-extrabold text-[#1E1915] mt-1">₱4,850.00 <span class="text-[10px] font-normal text-gray-500">× 1</span></div>
                            </div>
                        </div>
                    </div>

                    {{-- Summary Box --}}
                    <div class="p-4 rounded-2xl bg-[#FDF8EE] border border-[#E8DECB] space-y-2 text-xs">
                        <div class="flex justify-between text-[#766C60]">
                            <span>Items Subtotal (1 item)</span>
                            <span class="font-bold text-[#1E1915]">₱4,850.00</span>
                        </div>
                        <div class="flex justify-between text-[#766C60]">
                            <span>Estimated Shipping (Luzon)</span>
                            <span class="font-bold text-emerald-700">₱0.00 (Free)</span>
                        </div>
                        <div class="flex justify-between text-[#766C60]">
                            <span>Voucher (LUMBARONGWELCOME)</span>
                            <span class="font-bold text-[#C49520]">-₱200.00</span>
                        </div>
                        <div class="pt-2 border-t border-[#E8DECB] flex justify-between text-sm font-extrabold text-[#1E1915]">
                            <span>Estimated Total:</span>
                            <span>₱4,650.00</span>
                        </div>
                    </div>

                    <div class="pt-3 border-t border-[#E8DECB] flex items-center justify-between">
                        <button type="button" @click="goToStep(0)" class="px-4 py-2 rounded-full bg-gray-100 hover:bg-gray-200 text-gray-700 text-xs font-bold">
                            ← Back
                        </button>
                        <button type="button"
                                @click="goToStep(2)"
                                class="px-5 py-2.5 rounded-full bg-[#1E1915] text-white text-xs font-bold hover:bg-[#C49520] transition-all shadow-md flex items-center gap-1.5">
                            <span>Proceed to Checkout →</span>
                        </button>
                    </div>
                </div>

                {{-- STEP 3: Checkout & Payment Choice --}}
                <div x-show="currentStep === 2" x-transition class="space-y-5">
                    <div>
                        <span class="text-[10px] font-bold uppercase tracking-widest text-[#C49520]">Step 3: Fast & Secure Checkout</span>
                        <h3 class="font-serif text-xl sm:text-2xl font-bold text-[#1E1915]">Delivery & Payment</h3>
                        <p class="text-xs text-[#766C60] mt-1">Select your shipping destination and preferred payment method.</p>
                    </div>

                    {{-- Address Selection --}}
                    <div class="p-4 rounded-2xl bg-white border border-[#E8DECB] space-y-2">
                        <div class="flex items-center justify-between">
                            <span class="text-xs font-bold text-[#1E1915] flex items-center gap-1.5">
                                📍 Delivery Address
                            </span>
                            <span class="text-[10px] font-bold text-[#C49520] uppercase tracking-wider">Default</span>
                        </div>
                        <p class="text-xs text-[#766C60] leading-relaxed">
                            <strong class="text-[#1E1915]">Juan Dela Cruz</strong> (+63 917 123 4567)<br>
                            Unit 402, Heritage Residences, Quezon City, Metro Manila
                        </p>
                    </div>

                    {{-- Payment Method Selection --}}
                    <div class="space-y-2">
                        <label class="text-xs font-bold text-[#1E1915]">Select Payment Method (Simulation):</label>
                        <div class="grid grid-cols-2 sm:grid-cols-4 gap-2">
                            <template x-for="p in paymentMethods" :key="p.id">
                                <button type="button"
                                        @click="selectedPayment = p.id"
                                        class="p-3 rounded-2xl border text-center transition-all flex flex-col items-center justify-center gap-1"
                                        :class="selectedPayment === p.id ? 'border-[#C49520] bg-[#FFF9ED] ring-1 ring-[#C49520]' : 'border-[#E8DECB] bg-white hover:border-[#C49520]/50'">
                                    <span class="text-xl" x-text="p.icon"></span>
                                    <span class="text-xs font-bold text-[#1E1915]" x-text="p.name"></span>
                                </button>
                            </template>
                        </div>
                    </div>

                    {{-- Special Instructions to Artisan --}}
                    <div class="space-y-1">
                        <label class="text-xs font-bold text-[#1E1915]">Special Artisan Note (Optional):</label>
                        <input type="text"
                               placeholder="e.g. Please expedite for wedding on Saturday, need classic mandarin collar."
                               value="Kindly ensure soft inner collar lining. Thank you!"
                               readonly
                               class="w-full text-xs bg-[#FDF8EE] border border-[#E8DECB] rounded-xl p-3 text-[#1E1915]">
                    </div>

                    <div class="pt-3 border-t border-[#E8DECB] flex items-center justify-between">
                        <button type="button" @click="goToStep(1)" class="px-4 py-2 rounded-full bg-gray-100 hover:bg-gray-200 text-gray-700 text-xs font-bold">
                            ← Back
                        </button>
                        <button type="button"
                                @click="goToStep(3); startTrackingSimulation();"
                                class="px-6 py-2.5 rounded-full bg-emerald-700 text-white text-xs font-bold hover:bg-emerald-800 transition-all shadow-md flex items-center gap-1.5">
                            <span>Simulate Place Order ✓</span>
                        </button>
                    </div>
                </div>

                {{-- STEP 4: Live Order Lifecycle & Tracking Simulator --}}
                <div x-show="currentStep === 3" x-transition class="space-y-5">
                    <div class="flex items-start justify-between">
                        <div>
                            <span class="text-[10px] font-bold uppercase tracking-widest text-[#C49520]">Step 4: Order Lifecycle & Tracking</span>
                            <h3 class="font-serif text-xl sm:text-2xl font-bold text-[#1E1915]">Real-Time Order Tracking</h3>
                            <p class="text-xs text-[#766C60] mt-1">Experience how your order progresses from workshop to your doorstep.</p>
                        </div>
                        <span class="text-xs font-mono font-bold bg-[#1E1915] text-[#C49520] px-3 py-1 rounded-full">
                            #LMB-DEMO-8821
                        </span>
                    </div>

                    {{-- Interactive Status Stepper --}}
                    <div class="p-4 rounded-2xl bg-white border border-[#E8DECB] space-y-4">
                        <div class="grid grid-cols-4 gap-2 text-center relative">
                            {{-- Step 1: Placed --}}
                            <div class="space-y-1.5">
                                <div class="w-8 h-8 rounded-full mx-auto flex items-center justify-center text-xs font-bold transition-all"
                                     :class="orderStatusStage >= 1 ? 'bg-emerald-600 text-white shadow-sm' : 'bg-gray-200 text-gray-500'">
                                    1
                                </div>
                                <div class="text-[10px] font-bold text-[#1E1915]">Order Placed</div>
                                <div class="text-[9px] text-[#766C60]">Confirmed</div>
                            </div>

                            {{-- Step 2: Tailoring / Packing Proof --}}
                            <div class="space-y-1.5">
                                <div class="w-8 h-8 rounded-full mx-auto flex items-center justify-center text-xs font-bold transition-all"
                                     :class="orderStatusStage >= 2 ? 'bg-[#C49520] text-white shadow-sm ring-2 ring-[#C49520]/30' : 'bg-gray-200 text-gray-500'">
                                    2
                                </div>
                                <div class="text-[10px] font-bold text-[#1E1915]">Artisan Prep</div>
                                <div class="text-[9px] text-[#766C60]">Packing Proof</div>
                            </div>

                            {{-- Step 3: Shipped --}}
                            <div class="space-y-1.5">
                                <div class="w-8 h-8 rounded-full mx-auto flex items-center justify-center text-xs font-bold transition-all"
                                     :class="orderStatusStage >= 3 ? 'bg-blue-600 text-white shadow-sm' : 'bg-gray-200 text-gray-500'">
                                    3
                                </div>
                                <div class="text-[10px] font-bold text-[#1E1915]">Dispatched</div>
                                <div class="text-[9px] text-[#766C60]">In Transit</div>
                            </div>

                            {{-- Step 4: Delivered & Review --}}
                            <div class="space-y-1.5">
                                <div class="w-8 h-8 rounded-full mx-auto flex items-center justify-center text-xs font-bold transition-all"
                                     :class="orderStatusStage >= 4 ? 'bg-emerald-700 text-white shadow-sm' : 'bg-gray-200 text-gray-500'">
                                    4
                                </div>
                                <div class="text-[10px] font-bold text-[#1E1915]">Delivered</div>
                                <div class="text-[9px] text-[#766C60]">Confirm & Review</div>
                            </div>
                        </div>

                        {{-- Dynamic Stage Description Box --}}
                        <div class="p-3.5 rounded-xl bg-[#FDF8EE] border border-[#E8DECB] text-xs space-y-1.5">
                            <div class="font-bold text-[#1E1915] flex items-center gap-2">
                                <span class="w-2 h-2 rounded-full bg-[#C49520] animate-pulse"></span>
                                <span x-text="stageDetails[orderStatusStage].title"></span>
                            </div>
                            <p class="text-[#766C60] leading-relaxed" x-text="stageDetails[orderStatusStage].desc"></p>
                        </div>
                    </div>

                    {{-- Interactive Simulation Trigger Controls --}}
                    <div class="p-4 rounded-2xl bg-[#FFF9ED] border border-[#E8DECB] space-y-2">
                        <div class="text-xs font-bold text-[#1E1915]">⚡ Simulate Order Progression (Click to Test):</div>
                        <div class="flex flex-wrap gap-2">
                            <button type="button"
                                    @click="orderStatusStage = 1"
                                    class="px-3 py-1.5 rounded-lg text-[11px] font-bold border transition-all"
                                    :class="orderStatusStage === 1 ? 'bg-[#1E1915] text-white border-[#1E1915]' : 'bg-white text-gray-700 border-gray-200 hover:border-gray-400'">
                                1. Order Placed
                            </button>
                            <button type="button"
                                    @click="orderStatusStage = 2"
                                    class="px-3 py-1.5 rounded-lg text-[11px] font-bold border transition-all"
                                    :class="orderStatusStage === 2 ? 'bg-[#1E1915] text-white border-[#1E1915]' : 'bg-white text-gray-700 border-gray-200 hover:border-gray-400'">
                                2. Artisan Tailoring & Photo Proof
                            </button>
                            <button type="button"
                                    @click="orderStatusStage = 3"
                                    class="px-3 py-1.5 rounded-lg text-[11px] font-bold border transition-all"
                                    :class="orderStatusStage === 3 ? 'bg-[#1E1915] text-white border-[#1E1915]' : 'bg-white text-gray-700 border-gray-200 hover:border-gray-400'">
                                3. Shipped / Courier
                            </button>
                            <button type="button"
                                    @click="orderStatusStage = 4"
                                    class="px-3 py-1.5 rounded-lg text-[11px] font-bold border transition-all"
                                    :class="orderStatusStage === 4 ? 'bg-[#1E1915] text-white border-[#1E1915]' : 'bg-white text-gray-700 border-gray-200 hover:border-gray-400'">
                                4. Delivered & Confirm
                            </button>
                        </div>
                    </div>

                    <div class="pt-3 border-t border-[#E8DECB] flex items-center justify-between">
                        <button type="button" @click="goToStep(0)" class="px-4 py-2 rounded-full bg-gray-100 hover:bg-gray-200 text-gray-700 text-xs font-bold">
                            ↺ Restart Demo
                        </button>
                        <button type="button"
                                @click="closeDemo()"
                                class="px-6 py-2.5 rounded-full bg-[#1E1915] text-white text-xs font-bold hover:bg-[#C49520] transition-all shadow-md">
                            I'm Ready to Shop Real Barongs ✨
                        </button>
                    </div>
                </div>

            </div>
        </div>
    </div>
</div>

<script>
function orderProcessDemoEngine() {
    return {
        isOpen: false,
        currentStep: 0,
        selectedFabric: 'Piña Seda',
        selectedSize: 'L',
        isCustomSize: false,
        selectedPayment: 'gcash',
        orderStatusStage: 1,

        steps: [
            { shortTitle: 'Select & Size' },
            { shortTitle: 'Cart Review' },
            { shortTitle: 'Checkout' },
            { shortTitle: 'Live Tracking' }
        ],

        fabrics: [
            { name: 'Piña Seda', desc: 'Silk & Pineapple fiber blend' },
            { name: 'Piña Organza', desc: 'Fine sheer heritage weave' },
            { name: 'Cocoon Silk', desc: 'Luxurious opaque finish' }
        ],

        paymentMethods: [
            { id: 'gcash', name: 'GCash', icon: '📱' },
            { id: 'maya', name: 'Maya', icon: '💳' },
            { id: 'cod', name: 'Cash on Del.', icon: '💵' },
            { id: 'bank', name: 'Bank Transfer', icon: '🏦' }
        ],

        stageDetails: {
            1: {
                title: 'Order Placed & Notified',
                desc: 'The artisan tailor receives your sizing specifications and prepares the raw fabric.'
            },
            2: {
                title: 'Artisan Tailoring & Packing Proof Photo',
                desc: 'The seller crafts the embroidery and uploads photos of the finished piece and packed parcel before dispatch.'
            },
            3: {
                title: 'Dispatched with Courier Tracking',
                desc: 'Courier picks up the parcel from the artisan guild. You receive real-time parcel tracking updates.'
            },
            4: {
                title: 'Delivered — Verify Fit & Review',
                desc: 'You receive the barong, inspect the craftsmanship, click "Confirm Order Received", and share your rating & review!'
            }
        },

        openDemo() {
            this.isOpen = true;
            this.currentStep = 0;
            this.orderStatusStage = 1;
            document.body.classList.add('overflow-hidden');
        },

        closeDemo() {
            this.isOpen = false;
            document.body.classList.remove('overflow-hidden');
        },

        goToStep(step) {
            this.currentStep = step;
        },

        startTrackingSimulation() {
            this.orderStatusStage = 1;
            setTimeout(() => { if (this.isOpen && this.currentStep === 3) this.orderStatusStage = 2; }, 1800);
            setTimeout(() => { if (this.isOpen && this.currentStep === 3) this.orderStatusStage = 3; }, 3600);
            setTimeout(() => { if (this.isOpen && this.currentStep === 3) this.orderStatusStage = 4; }, 5400);
        }
    };
}
</script>
