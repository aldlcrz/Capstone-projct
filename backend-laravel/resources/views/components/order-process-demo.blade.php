@php
    // Fetch real active products from database
    $realProducts = \App\Models\Product::with('seller')
        ->where('status', 'approved')
        ->latest('updatedAt')
        ->take(6)
        ->get();

    if ($realProducts->isEmpty()) {
        $realProducts = \App\Models\Product::with('seller')->latest('updatedAt')->take(6)->get();
    }

    $currentUser = Auth::user();
    $userAddress = null;
    if ($currentUser) {
        $userAddress = \App\Models\Address::where('userId', $currentUser->id)->where('isDefault', true)->first()
            ?? \App\Models\Address::where('userId', $currentUser->id)->first();
    }

    $addressString = $userAddress
        ? trim(($userAddress->houseNo ? $userAddress->houseNo . ' ' : '') . ($userAddress->street ? $userAddress->street . ', ' : '') . ($userAddress->barangay ? $userAddress->barangay . ', ' : '') . ($userAddress->city ? $userAddress->city . ', ' : '') . ($userAddress->province ?? ''))
        : ($currentUser ? 'Default delivery address' : 'Heritage Residences, Quezon City, Metro Manila');

    $recipientName = $userAddress->recipientName ?? ($currentUser->name ?? 'Juan Dela Cruz');
    $recipientPhone = $userAddress->phone ?? ($currentUser->mobileNumber ?? '+63 912 345 6789');

    // Format products JSON for Alpine
    $productsArray = $realProducts->map(function(\App\Models\Product $p) {
        $seller = $p->seller;
        $sizes = is_array($p->sizes) && count($p->sizes) > 0 ? $p->sizes : ['S', 'M', 'L', 'XL', '2XL'];
        return [
            'id' => $p->id,
            'name' => $p->name,
            'price' => (float)$p->price,
            'formatted_price' => '₱' . number_format($p->price, 2),
            'image' => $p->image_url ?? $p->getImageUrl(),
            'sizes' => $sizes,
            'fabric' => $p->fabric_type ?: 'Piña-Seda / Heritage Weave',
            'seller_name' => $seller->shopName ?: ($seller->name ?? 'Lumban Master Tailor'),
            'seller_photo' => $seller->profilePhoto ? (str_starts_with($seller->profilePhoto, 'http') ? $seller->profilePhoto : url($seller->profilePhoto)) : null,
            'artisan_region' => $p->artisan_region ?: ($seller->shopCity ? $seller->shopCity . ', ' . $seller->shopProvince : 'Lumban, Laguna'),
            'shipping_fee' => (float)($p->shippingFee ?? 0),
        ];
    })->values()->toArray();
@endphp

<script>
window.__realProductsDemo = @json($productsArray);
window.__demoUserData = {
    name: '{{ addslashes($recipientName) }}',
    phone: '{{ addslashes($recipientPhone) }}',
    address: '{{ addslashes($addressString) }}',
    isLoggedIn: {{ Auth::check() ? 'true' : 'false' }}
};
</script>

{{-- Interactive Real-Time Ordering Process Demo Simulator --}}
<div x-data="realtimeOrderDemoEngine()"
     @open-order-demo.window="openDemo()"
     x-cloak
     x-show="isOpen"
     class="fixed inset-0 z-10000 overflow-y-auto"
     style="display: none;"
     aria-labelledby="order-demo-title"
     role="dialog"
     aria-modal="true">

    {{-- Backdrop --}}
    <div class="fixed inset-0 bg-black/80 backdrop-blur-md transition-opacity"
         x-show="isOpen"
         x-transition:enter="ease-out duration-300"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="ease-in duration-200"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         @click="closeDemo()"></div>

    <div class="flex min-h-full items-center justify-center p-3 sm:p-4 text-center">
        <div class="relative w-full max-w-2xl transform overflow-hidden rounded-3xl text-left align-middle shadow-2xl transition-all"
             style="background-color: #FFFCF7; border: 1px solid #E8DECB;"
             x-show="isOpen"
             x-transition:enter="ease-out duration-300"
             x-transition:enter-start="opacity-0 scale-95 translate-y-4"
             x-transition:enter-end="opacity-100 scale-100 translate-y-0"
             x-transition:leave="ease-in duration-200"
             x-transition:leave-start="opacity-100 scale-100 translate-y-0"
             x-transition:leave-end="opacity-0 scale-95 translate-y-4"
             @click.stop>

            {{-- Top Banner: Real-Time Demo Indicator --}}
            <div class="px-5 py-3.5 flex items-center justify-between border-b"
                 style="background-color: #1E1915; border-color: #3B3228;">
                <div class="flex items-center gap-2.5">
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[10px] font-extrabold uppercase tracking-wider"
                          style="background-color: #C49520; color: #1E1915;">
                        Live System Demo
                    </span>
                    <span class="text-xs font-semibold text-white/90 hidden sm:inline">
                        Interactive ordering walkthrough using real catalog products (0 real cost)
                    </span>
                </div>
                <button type="button"
                        @click="closeDemo()"
                        class="text-gray-300 hover:text-white text-sm font-bold w-7 h-7 rounded-full flex items-center justify-center hover:bg-white/15 transition-colors"
                        title="Close Demo">
                    ✕
                </button>
            </div>

            {{-- Step Navigation Stepper --}}
            <div class="px-4 sm:px-6 py-3 border-b overflow-x-auto no-scrollbar"
                 style="background-color: #FDF8EE; border-color: #E8DECB;">
                <div class="flex items-center justify-between min-w-90 gap-2">
                    <template x-for="(s, index) in steps" :key="index">
                        <button type="button"
                                @click="currentStep = index"
                                class="flex items-center gap-2 text-left transition-all group cursor-pointer"
                                :class="{ 'opacity-100': currentStep === index, 'opacity-60 hover:opacity-90': currentStep !== index }">
                            <span class="w-6 h-6 rounded-full flex items-center justify-center text-[10px] font-extrabold shrink-0 transition-colors"
                                  :style="currentStep === index ? 'background-color: #C49520; color: #FFFFFF;' : (currentStep > index ? 'background-color: #1E1915; color: #FFFFFF;' : 'background-color: #E8DECB; color: #766C60;')">
                                <span x-show="currentStep <= index" x-text="index + 1"></span>
                                <span x-show="currentStep > index">✓</span>
                            </span>
                            <div class="min-w-0">
                                <div class="text-[9px] uppercase tracking-wider font-extrabold" style="color: #A89887;" x-text="'Step ' + (index + 1)"></div>
                                <div class="text-xs font-bold" style="color: #1E1915;" x-text="s.shortTitle"></div>
                            </div>
                        </button>
                    </template>
                </div>
            </div>

            {{-- Step Content Area --}}
            <div class="p-5 sm:p-7 max-h-[70vh] overflow-y-auto" style="background-color: #FFFCF7;">

                {{-- STEP 1: Select Real Product & Fit --}}
                <div x-show="currentStep === 0" x-transition class="space-y-5">
                    
                    {{-- Real Product Selector Bar (If multiple real products exist) --}}
                    <template x-if="products.length > 1">
                        <div class="space-y-1.5 pb-3 border-b" style="border-color: #E8DECB;">
                            <label class="text-[11px] font-extrabold uppercase tracking-wider" style="color: #766C60;">
                                Select Live Product to Demo:
                            </label>
                            <div class="flex items-center gap-2 overflow-x-auto no-scrollbar pb-1">
                                <template x-for="(prod, idx) in products" :key="prod.id">
                                    <button type="button"
                                            @click="selectProduct(idx)"
                                            class="px-3 py-1.5 rounded-xl text-xs font-bold shrink-0 transition-all cursor-pointer border flex items-center gap-1.5"
                                            :style="selectedProductIndex === idx ? 'background-color: #1E1915; color: #FFFFFF; border-color: #1E1915;' : 'background-color: #FFFFFF; color: #1E1915; border-color: #E8DECB;'">
                                        <span x-text="prod.name"></span>
                                        <span class="text-[10px] font-semibold opacity-80" x-text="prod.formatted_price"></span>
                                    </button>
                                </template>
                            </div>
                        </div>
                    </template>

                    {{-- Active Product Header --}}
                    <div class="flex items-start justify-between gap-4">
                        <div class="flex items-start gap-3.5 min-w-0">
                            {{-- Product Real Image --}}
                            <div class="w-16 h-16 sm:w-20 sm:h-20 rounded-2xl overflow-hidden border shrink-0 bg-white flex items-center justify-center shadow-xs" style="border-color: #E8DECB;">
                                <img :src="currentProduct.image" :alt="currentProduct.name" class="w-full h-full object-cover">
                            </div>
                            <div class="min-w-0">
                                <span class="text-[10px] font-extrabold uppercase tracking-widest" style="color: #C49520;">Step 1: Choose Fit & Style</span>
                                <h3 class="font-serif text-lg sm:text-xl font-extrabold leading-tight mt-0.5 truncate" style="color: #1E1915;" x-text="currentProduct.name"></h3>
                                <p class="text-xs mt-1 truncate" style="color: #5C5247;">
                                    Artisan: <strong style="color: #1E1915;" x-text="currentProduct.seller_name"></strong> • <span x-text="currentProduct.artisan_region"></span>
                                </p>
                            </div>
                        </div>
                        <div class="text-right shrink-0">
                            <div class="text-xl font-black" style="color: #1E1915;" x-text="currentProduct.formatted_price"></div>
                            <span class="inline-block text-[9px] font-bold text-emerald-700 bg-emerald-50 px-2 py-0.5 rounded border border-emerald-200">Free Shipping</span>
                        </div>
                    </div>

                    {{-- Fabric Type --}}
                    <div class="p-3.5 rounded-2xl border space-y-1" style="background-color: #FFFFFF; border-color: #E8DECB;">
                        <div class="text-[11px] font-extrabold uppercase tracking-wider" style="color: #766C60;">Fabric Spec</div>
                        <div class="text-xs font-bold" style="color: #1E1915;" x-text="currentProduct.fabric"></div>
                    </div>

                    {{-- Size Selector --}}
                    <div class="space-y-2">
                        <div class="flex items-center justify-between">
                            <label class="text-xs font-bold" style="color: #1E1915;">Available Sizing:</label>
                            <button type="button" @click="isCustomSize = !isCustomSize" class="text-[11px] font-bold underline cursor-pointer" style="color: #C49520;">
                                <span x-text="isCustomSize ? 'Switch to Standard Sizes' : 'Request Made-to-Measure (+₱0)'"></span>
                            </button>
                        </div>

                        {{-- Standard Sizes --}}
                        <div x-show="!isCustomSize" class="flex flex-wrap gap-2">
                            <template x-for="sz in (currentProduct.sizes || ['S', 'M', 'L', 'XL'])" :key="sz">
                                <button type="button"
                                        @click="selectedSize = sz"
                                        class="px-4 h-10 rounded-xl border text-xs font-bold transition-all flex items-center justify-center cursor-pointer shadow-xs"
                                        :style="selectedSize === sz ? 'background-color: #1E1915; color: #FFFFFF; border: 1px solid #1E1915;' : 'background-color: #FFFFFF; color: #1E1915; border: 1px solid #E8DECB;'">
                                    <span x-text="sz"></span>
                                </button>
                            </template>
                        </div>

                        {{-- Made to Measure Input --}}
                        <div x-show="isCustomSize" class="p-4 rounded-2xl border space-y-2.5" style="background-color: #FFFFFF; border-color: #E8DECB;">
                            <div class="text-xs font-bold flex items-center gap-1.5" style="color: #1E1915;">
                                <span>📐 Custom Sizing Specifications (Inches)</span>
                            </div>
                            <div class="grid grid-cols-3 gap-2">
                                <div>
                                    <label class="text-[10px] font-semibold" style="color: #5C5247;">Chest</label>
                                    <input type="text" value="38 in" readonly class="w-full text-xs font-bold rounded-lg px-2.5 py-1.5 text-center" style="background-color: #FDF8EE; border: 1px solid #E8DECB; color: #1E1915;">
                                </div>
                                <div>
                                    <label class="text-[10px] font-semibold" style="color: #5C5247;">Shoulder</label>
                                    <input type="text" value="17.5 in" readonly class="w-full text-xs font-bold rounded-lg px-2.5 py-1.5 text-center" style="background-color: #FDF8EE; border: 1px solid #E8DECB; color: #1E1915;">
                                </div>
                                <div>
                                    <label class="text-[10px] font-semibold" style="color: #5C5247;">Sleeve Length</label>
                                    <input type="text" value="24.5 in" readonly class="w-full text-xs font-bold rounded-lg px-2.5 py-1.5 text-center" style="background-color: #FDF8EE; border: 1px solid #E8DECB; color: #1E1915;">
                                </div>
                            </div>
                            <p class="text-[10px] text-gray-500 italic">Custom measurements will be submitted directly with this order to the artisan.</p>
                        </div>
                    </div>

                    <div class="pt-3 border-t flex items-center justify-between" style="border-color: #E8DECB;">
                        <div class="text-xs" style="color: #5C5247;">
                            Selected: <strong style="color: #1E1915;" x-text="isCustomSize ? 'Custom Made-to-Measure' : 'Size ' + selectedSize"></strong>
                        </div>
                        <button type="button"
                                @click="goToStep(1)"
                                style="background-color: #1E1915; color: #FFFFFF;"
                                onmouseover="this.style.backgroundColor='#C49520';"
                                onmouseout="this.style.backgroundColor='#1E1915';"
                                class="px-5 py-2.5 rounded-full text-xs font-bold transition-all shadow-md flex items-center gap-1.5 cursor-pointer">
                            <span>Add to Cart & Next →</span>
                        </button>
                    </div>
                </div>

                {{-- STEP 2: Cart Review --}}
                <div x-show="currentStep === 1" x-transition class="space-y-5">
                    <div class="flex items-start justify-between">
                        <div>
                            <span class="text-[10px] font-extrabold uppercase tracking-widest" style="color: #C49520;">Step 2: Review Shopping Cart</span>
                            <h3 class="font-serif text-xl sm:text-2xl font-extrabold mt-0.5" style="color: #1E1915;">Your Shopping Bag</h3>
                            <p class="text-xs mt-1" style="color: #5C5247;">Items are grouped by each master tailor's workshop.</p>
                        </div>
                    </div>

                    {{-- Cart Item Card --}}
                    <div class="p-4 rounded-2xl border space-y-3" style="background-color: #FFFFFF; border-color: #E8DECB;">
                        <div class="flex items-center justify-between border-b border-gray-100 pb-2">
                            <div class="flex items-center gap-2">
                                <span class="w-2 h-2 rounded-full bg-emerald-500"></span>
                                <span class="text-xs font-bold" style="color: #1E1915;" x-text="currentProduct.seller_name"></span>
                            </div>
                            <span class="text-[10px] font-extrabold uppercase tracking-wider" style="color: #C49520;" x-text="currentProduct.artisan_region"></span>
                        </div>

                        <div class="flex items-center gap-3">
                            <div class="w-14 h-14 rounded-xl border overflow-hidden flex items-center justify-center shrink-0 bg-white" style="border-color: #E8DECB;">
                                <img :src="currentProduct.image" :alt="currentProduct.name" class="w-full h-full object-cover">
                            </div>
                            <div class="flex-1 min-w-0">
                                <h4 class="text-xs font-bold truncate" style="color: #1E1915;" x-text="currentProduct.name"></h4>
                                <div class="text-[11px]" style="color: #5C5247;" x-text="(isCustomSize ? 'Custom Fit' : 'Size: ' + selectedSize) + ' • ' + currentProduct.fabric"></div>
                                <div class="text-xs font-extrabold mt-1" style="color: #1E1915;">
                                    <span x-text="currentProduct.formatted_price"></span> <span class="text-[10px] font-normal text-gray-500">× 1</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Summary Calculation --}}
                    <div class="p-4 rounded-2xl border space-y-2 text-xs" style="background-color: #FDF8EE; border-color: #E8DECB;">
                        <div class="flex justify-between" style="color: #5C5247;">
                            <span>Subtotal (1 item)</span>
                            <span class="font-bold" style="color: #1E1915;" x-text="currentProduct.formatted_price"></span>
                        </div>
                        <div class="flex justify-between" style="color: #5C5247;">
                            <span>Shipping Fee</span>
                            <span class="font-bold text-emerald-700">₱0.00 (Free)</span>
                        </div>
                        <div class="flex justify-between" style="color: #5C5247;">
                            <span>Promo Voucher (LUMBARONGPROMO)</span>
                            <span class="font-bold" style="color: #C49520;">-₱200.00</span>
                        </div>
                        <div class="pt-2 border-t flex justify-between text-sm font-extrabold" style="border-color: #E8DECB; color: #1E1915;">
                            <span>Total Payable:</span>
                            <span x-text="'₱' + (Math.max(0, currentProduct.price - 200)).toLocaleString('en-US', { minimumFractionDigits: 2 })"></span>
                        </div>
                    </div>

                    <div class="pt-3 border-t flex items-center justify-between" style="border-color: #E8DECB;">
                        <button type="button" @click="goToStep(0)" class="px-4 py-2 rounded-full bg-gray-100 hover:bg-gray-200 text-gray-700 text-xs font-bold cursor-pointer">
                            ← Back
                        </button>
                        <button type="button"
                                @click="goToStep(2)"
                                style="background-color: #1E1915; color: #FFFFFF;"
                                onmouseover="this.style.backgroundColor='#C49520';"
                                onmouseout="this.style.backgroundColor='#1E1915';"
                                class="px-5 py-2.5 rounded-full text-xs font-bold transition-all shadow-md flex items-center gap-1.5 cursor-pointer">
                            <span>Proceed to Checkout →</span>
                        </button>
                    </div>
                </div>

                {{-- STEP 3: Real Checkout Simulation --}}
                <div x-show="currentStep === 2" x-transition class="space-y-5">
                    <div>
                        <span class="text-[10px] font-extrabold uppercase tracking-widest" style="color: #C49520;">Step 3: Secure Artisan Checkout</span>
                        <h3 class="font-serif text-xl sm:text-2xl font-extrabold mt-0.5" style="color: #1E1915;">Delivery & Payment Verification</h3>
                        <p class="text-xs mt-1" style="color: #5C5247;">In LumBarong, payments are sent directly to the artisan's verified account with reference verification.</p>
                    </div>

                    {{-- Address Box --}}
                    <div class="p-4 rounded-2xl border space-y-2" style="background-color: #FFFFFF; border-color: #E8DECB;">
                        <div class="flex items-center justify-between">
                            <span class="text-xs font-bold flex items-center gap-1.5" style="color: #1E1915;">
                                📍 Delivery Destination
                            </span>
                            <span class="text-[10px] font-extrabold uppercase tracking-wider" style="color: #C49520;">Verified Address</span>
                        </div>
                        <p class="text-xs leading-relaxed" style="color: #5C5247;">
                            <strong style="color: #1E1915;" x-text="userData.name"></strong> (<span x-text="userData.phone"></span>)<br>
                            <span x-text="userData.address"></span>
                        </p>
                    </div>

                    {{-- Payment Method Selection --}}
                    <div class="space-y-2">
                        <label class="text-xs font-bold" style="color: #1E1915;">Payment Method:</label>
                        <div class="grid grid-cols-3 gap-2">
                            <button type="button"
                                    @click="selectedPayment = 'GCash'"
                                    class="p-3 rounded-2xl border text-center transition-all flex flex-col items-center justify-center gap-1 cursor-pointer"
                                    :style="selectedPayment === 'GCash' ? 'background-color: #FFF9ED; border: 2px solid #C49520;' : 'background-color: #FFFFFF; border: 1px solid #E8DECB;'">
                                <span class="text-xl">📱</span>
                                <span class="text-xs font-bold" style="color: #1E1915;">GCash (13 Digits)</span>
                            </button>
                            <button type="button"
                                    @click="selectedPayment = 'Maya'"
                                    class="p-3 rounded-2xl border text-center transition-all flex flex-col items-center justify-center gap-1 cursor-pointer"
                                    :style="selectedPayment === 'Maya' ? 'background-color: #FFF9ED; border: 2px solid #C49520;' : 'background-color: #FFFFFF; border: 1px solid #E8DECB;'">
                                <span class="text-xl">💳</span>
                                <span class="text-xs font-bold" style="color: #1E1915;">Maya (12 Digits)</span>
                            </button>
                            <button type="button"
                                    @click="selectedPayment = 'COD'"
                                    class="p-3 rounded-2xl border text-center transition-all flex flex-col items-center justify-center gap-1 cursor-pointer"
                                    :style="selectedPayment === 'COD' ? 'background-color: #FFF9ED; border: 2px solid #C49520;' : 'background-color: #FFFFFF; border: 1px solid #E8DECB;'">
                                <span class="text-xl">💵</span>
                                <span class="text-xs font-bold" style="color: #1E1915;">Cash on Delivery</span>
                            </button>
                        </div>
                    </div>

                    {{-- Simulated Reference Number Input --}}
                    <div x-show="selectedPayment !== 'COD'" class="p-3.5 rounded-2xl border space-y-1.5" style="background-color: #FDF8EE; border-color: #E8DECB;">
                        <label class="text-[11px] font-bold" style="color: #1E1915;">
                            Payment Reference Number (<span x-text="selectedPayment"></span> Verification):
                        </label>
                        <input type="text"
                               :value="selectedPayment === 'GCash' ? '1002948291048' : '982740192837'"
                               readonly
                               class="w-full text-xs font-mono font-bold rounded-lg p-2.5"
                               style="background-color: #FFFFFF; border: 1px solid #E8DECB; color: #1E1915;">
                        <p class="text-[10px] text-gray-500 italic">In live ordering, upload your receipt screenshot and enter your transaction reference number.</p>
                    </div>

                    <div class="pt-3 border-t flex items-center justify-between" style="border-color: #E8DECB;">
                        <button type="button" @click="goToStep(1)" class="px-4 py-2 rounded-full bg-gray-100 hover:bg-gray-200 text-gray-700 text-xs font-bold cursor-pointer">
                            ← Back
                        </button>
                        <button type="button"
                                @click="goToStep(3); startTrackingSimulation();"
                                class="px-6 py-2.5 rounded-full bg-emerald-700 text-white text-xs font-bold hover:bg-emerald-800 transition-all shadow-md flex items-center gap-1.5 cursor-pointer">
                            <span>Simulate Place Order ✓</span>
                        </button>
                    </div>
                </div>

                {{-- STEP 4: Live Real-Time Order Lifecycle Simulator --}}
                <div x-show="currentStep === 3" x-transition class="space-y-5">
                    <div class="flex items-start justify-between">
                        <div>
                            <span class="text-[10px] font-extrabold uppercase tracking-widest" style="color: #C49520;">Step 4: Real Order Lifecycle</span>
                            <h3 class="font-serif text-xl sm:text-2xl font-extrabold mt-0.5" style="color: #1E1915;">Live Order Tracking</h3>
                            <p class="text-xs mt-1" style="color: #5C5247;">Track every milestone from tailoring to doorstep delivery.</p>
                        </div>
                        <span class="text-xs font-mono font-bold px-3 py-1 rounded-full shrink-0" style="background-color: #1E1915; color: #C49520;" x-text="'#LMB-' + (Math.floor(100000 + Math.random() * 900000))">
                        </span>
                    </div>

                    {{-- Real Status Timeline --}}
                    <div class="p-4 rounded-2xl border space-y-4" style="background-color: #FFFFFF; border-color: #E8DECB;">
                        <div class="grid grid-cols-4 gap-2 text-center relative">
                            {{-- Step 1 --}}
                            <div class="space-y-1.5">
                                <div class="w-8 h-8 rounded-full mx-auto flex items-center justify-center text-xs font-bold transition-all"
                                     :class="orderStatusStage >= 1 ? 'bg-emerald-600 text-white shadow-sm' : 'bg-gray-200 text-gray-500'">
                                    1
                                </div>
                                <div class="text-[10px] font-bold" style="color: #1E1915;">Pending</div>
                                <div class="text-[9px]" style="color: #5C5247;">Payment Check</div>
                            </div>

                            {{-- Step 2 --}}
                            <div class="space-y-1.5">
                                <div class="w-8 h-8 rounded-full mx-auto flex items-center justify-center text-xs font-bold transition-all"
                                     :style="orderStatusStage >= 2 ? 'background-color: #C49520; color: #FFFFFF;' : 'background-color: #E5E7EB; color: #6B7280;'">
                                    2
                                </div>
                                <div class="text-[10px] font-bold" style="color: #1E1915;">Preparing</div>
                                <div class="text-[9px]" style="color: #5C5247;">Packing Proof</div>
                            </div>

                            {{-- Step 3 --}}
                            <div class="space-y-1.5">
                                <div class="w-8 h-8 rounded-full mx-auto flex items-center justify-center text-xs font-bold transition-all"
                                     :class="orderStatusStage >= 3 ? 'bg-blue-600 text-white shadow-sm' : 'bg-gray-200 text-gray-500'">
                                    3
                                </div>
                                <div class="text-[10px] font-bold" style="color: #1E1915;">Shipped</div>
                                <div class="text-[9px]" style="color: #5C5247;">J&T / LBC Courier</div>
                            </div>

                            {{-- Step 4 --}}
                            <div class="space-y-1.5">
                                <div class="w-8 h-8 rounded-full mx-auto flex items-center justify-center text-xs font-bold transition-all"
                                     :class="orderStatusStage >= 4 ? 'bg-emerald-700 text-white shadow-sm' : 'bg-gray-200 text-gray-500'">
                                    4
                                </div>
                                <div class="text-[10px] font-bold" style="color: #1E1915;">Delivered</div>
                                <div class="text-[9px]" style="color: #5C5247;">Confirm & Review</div>
                            </div>
                        </div>

                        {{-- Stage Details Box --}}
                        <div class="p-3.5 rounded-xl border text-xs space-y-1.5" style="background-color: #FDF8EE; border-color: #E8DECB;">
                            <div class="font-bold flex items-center gap-2" style="color: #1E1915;">
                                <span class="w-2 h-2 rounded-full animate-pulse" style="background-color: #C49520;"></span>
                                <span x-text="stageDetails[orderStatusStage].title"></span>
                            </div>
                            <p class="leading-relaxed" style="color: #5C5247;" x-text="stageDetails[orderStatusStage].desc"></p>
                        </div>
                    </div>

                    {{-- Manual Progression Controls --}}
                    <div class="p-4 rounded-2xl border space-y-2" style="background-color: #FFF9ED; border-color: #E8DECB;">
                        <div class="text-xs font-bold" style="color: #1E1915;">⚡ Test Order Milestones:</div>
                        <div class="flex flex-wrap gap-2">
                            <button type="button"
                                    @click="orderStatusStage = 1"
                                    class="px-3 py-1.5 rounded-lg text-[11px] font-bold border transition-all cursor-pointer"
                                    :style="orderStatusStage === 1 ? 'background-color: #1E1915; color: #FFFFFF; border-color: #1E1915;' : 'background-color: #FFFFFF; color: #1E1915; border-color: #E8DECB;'">
                                1. Order Placed
                            </button>
                            <button type="button"
                                    @click="orderStatusStage = 2"
                                    class="px-3 py-1.5 rounded-lg text-[11px] font-bold border transition-all cursor-pointer"
                                    :style="orderStatusStage === 2 ? 'background-color: #1E1915; color: #FFFFFF; border-color: #1E1915;' : 'background-color: #FFFFFF; color: #1E1915; border-color: #E8DECB;'">
                                2. Seller Uploads Packing Proof
                            </button>
                            <button type="button"
                                    @click="orderStatusStage = 3"
                                    class="px-3 py-1.5 rounded-lg text-[11px] font-bold border transition-all cursor-pointer"
                                    :style="orderStatusStage === 3 ? 'background-color: #1E1915; color: #FFFFFF; border-color: #1E1915;' : 'background-color: #FFFFFF; color: #1E1915; border-color: #E8DECB;'">
                                3. Courier Picked Up & In Transit
                            </button>
                            <button type="button"
                                    @click="orderStatusStage = 4"
                                    class="px-3 py-1.5 rounded-lg text-[11px] font-bold border transition-all cursor-pointer"
                                    :style="orderStatusStage === 4 ? 'background-color: #1E1915; color: #FFFFFF; border-color: #1E1915;' : 'background-color: #FFFFFF; color: #1E1915; border-color: #E8DECB;'">
                                4. Delivered — Confirm & Review
                            </button>
                        </div>
                    </div>

                    <div class="pt-3 border-t flex items-center justify-between" style="border-color: #E8DECB;">
                        <button type="button" @click="goToStep(0)" class="px-4 py-2 rounded-full bg-gray-100 hover:bg-gray-200 text-gray-700 text-xs font-bold cursor-pointer">
                            ↺ Restart Demo
                        </button>
                        <button type="button"
                                @click="closeDemo()"
                                style="background-color: #1E1915; color: #FFFFFF;"
                                onmouseover="this.style.backgroundColor='#C49520';"
                                onmouseout="this.style.backgroundColor='#1E1915';"
                                class="px-6 py-2.5 rounded-full text-xs font-bold transition-all shadow-md cursor-pointer">
                            I'm Ready to Shop Real Barongs ✨
                        </button>
                    </div>
                </div>

            </div>
        </div>
    </div>
</div>

<script>
function realtimeOrderDemoEngine() {
    return {
        isOpen: false,
        currentStep: 0,
        selectedProductIndex: 0,
        products: Array.isArray(window.__realProductsDemo) && window.__realProductsDemo.length > 0 
            ? window.__realProductsDemo 
            : [{
                name: 'Heritage Piña Barong',
                price: 4850,
                formatted_price: '₱4,850.00',
                image: '/uploads/products/default.jpg',
                sizes: ['S', 'M', 'L', 'XL'],
                fabric: 'Piña-Seda Weave',
                seller_name: 'Lumban Artisan Guild',
                artisan_region: 'Lumban, Laguna'
            }],
        userData: window.__demoUserData || {
            name: 'Juan Dela Cruz',
            phone: '+63 912 345 6789',
            address: 'Heritage Residences, Quezon City'
        },
        selectedSize: 'L',
        isCustomSize: false,
        selectedPayment: 'GCash',
        orderStatusStage: 1,

        steps: [
            { shortTitle: 'Select & Size' },
            { shortTitle: 'Cart Review' },
            { shortTitle: 'Checkout' },
            { shortTitle: 'Live Tracking' }
        ],

        get currentProduct() {
            return this.products[this.selectedProductIndex] || this.products[0] || {};
        },

        get stageDetails() {
            const seller = this.currentProduct.seller_name || 'The artisan';
            return {
                1: {
                    title: 'Order Placed (Pending Confirmation)',
                    desc: `${seller} receives your order notification and verifies your ${this.selectedPayment} reference number.`
                },
                2: {
                    title: 'Preparing / Packing Proof Uploaded',
                    desc: `${seller} custom-tailors your ${this.currentProduct.name} and uploads a photo proof of the finished barong before dispatch.`
                },
                3: {
                    title: 'Shipped (Courier In Transit)',
                    desc: `Courier picks up your parcel from ${this.currentProduct.artisan_region}. You can track the delivery progress in real time under My Purchase.`
                },
                4: {
                    title: 'Delivered (Inspect, Confirm & Review)',
                    desc: `You receive the barong at ${this.userData.address}, click "Confirm Order Received", and share your rating and review for ${seller}!`
                }
            };
        },

        openDemo() {
            this.isOpen = true;
            this.currentStep = 0;
            this.orderStatusStage = 1;
            if (this.currentProduct.sizes && this.currentProduct.sizes.length > 0) {
                this.selectedSize = this.currentProduct.sizes[0];
            }
            document.body.classList.add('overflow-hidden');
        },

        closeDemo() {
            this.isOpen = false;
            document.body.classList.remove('overflow-hidden');
        },

        selectProduct(idx) {
            this.selectedProductIndex = idx;
            if (this.currentProduct.sizes && this.currentProduct.sizes.length > 0) {
                this.selectedSize = this.currentProduct.sizes[0];
            }
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
