<div x-data="{ 
        isOpen: false, 
        currentTab: 'about',
        openModal(tab) {
            this.currentTab = tab || 'about';
            this.isOpen = true;
        },
        closeModal() {
            this.isOpen = false;
        }
    }"
    @open-page-modal.window="openModal($event.detail ? $event.detail.tab : 'about')"
    @keydown.escape.window="if(isOpen) closeModal()"
    x-show="isOpen"
    x-cloak
    class="fixed inset-0 z-50 flex items-center justify-center p-3 sm:p-6"
    style="display: none;"
>
    <!-- Backdrop (absolute, matching standard modal architecture) -->
    <div 
        @click="closeModal()" 
        class="absolute inset-0 bg-black/60 backdrop-blur-sm"
    ></div>

    <!-- Modal Container -->
    <div 
        @click.stop
        class="bg-white w-full max-w-3xl rounded-3xl shadow-2xl border border-gray-100 relative z-10 flex flex-col max-h-[88vh] overflow-hidden"
        x-transition:enter="transition ease-out duration-300 transform"
        x-transition:enter-start="opacity-0 scale-95 translate-y-4"
        x-transition:enter-end="opacity-100 scale-100 translate-y-0"
        x-transition:leave="transition ease-in duration-200 transform"
        x-transition:leave-start="opacity-100 scale-100 translate-y-0"
        x-transition:leave-end="opacity-0 scale-95 translate-y-4"
    >
        <!-- Modal Header with Tab Selector -->
        <div class="px-6 pt-6 pb-4 border-b border-gray-100 shrink-0 bg-white">
            <div class="flex items-center justify-between mb-4">
                <div class="flex items-center gap-2">
                    <img src="{{ asset('images/logo-icon.png') }}" alt="LumBarong" class="w-6 h-6 object-contain rounded-full shadow-xs">
                    <span class="px-2.5 py-0.5 bg-[#C0420A]/10 text-[#C0420A] text-[9px] font-black uppercase tracking-widest rounded-full">
                        LumBarong
                    </span>
                </div>
                <button 
                    type="button" 
                    @click="closeModal()" 
                    class="w-9 h-9 flex items-center justify-center rounded-full text-gray-400 hover:text-black hover:bg-gray-100 transition-colors cursor-pointer"
                    title="Close Modal"
                >
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>

            <!-- Tab Buttons -->
            <div class="flex items-center gap-1.5 sm:gap-2 p-1 bg-gray-50 rounded-2xl border border-gray-100">
                <button 
                    type="button" 
                    @click="currentTab = 'about'"
                    :class="currentTab === 'about' ? 'bg-white text-black font-bold shadow-xs border border-gray-100' : 'text-gray-500 hover:text-black font-medium'"
                    class="flex-1 py-2 px-3 rounded-xl text-xs transition-all cursor-pointer text-center truncate"
                >
                    About LumBarong
                </button>
                <button 
                    type="button" 
                    @click="currentTab = 'privacy'"
                    :class="currentTab === 'privacy' ? 'bg-white text-black font-bold shadow-xs border border-gray-100' : 'text-gray-500 hover:text-black font-medium'"
                    class="flex-1 py-2 px-3 rounded-xl text-xs transition-all cursor-pointer text-center truncate"
                >
                    Privacy Policy
                </button>
                <button 
                    type="button" 
                    @click="currentTab = 'terms'"
                    :class="currentTab === 'terms' ? 'bg-white text-black font-bold shadow-xs border border-gray-100' : 'text-gray-500 hover:text-black font-medium'"
                    class="flex-1 py-2 px-3 rounded-xl text-xs transition-all cursor-pointer text-center truncate"
                >
                    Terms & Conditions
                </button>
            </div>
        </div>

        <!-- Modal Body (Scrollable) -->
        <div class="p-6 sm:p-8 overflow-y-auto no-scrollbar space-y-6 text-gray-600 text-sm leading-relaxed grow">
            
            {{-- TAB 1: ABOUT LUMBARONG --}}
            <div x-show="currentTab === 'about'" class="space-y-6">
                <div class="text-center pb-4 border-b border-gray-100">
                    <h2 class="font-serif text-2xl sm:text-3xl font-bold text-gray-900 mb-2">Authentic Filipino Heritage</h2>
                    <p class="text-gray-500 text-xs sm:text-sm max-w-lg mx-auto">
                        Connecting the master embroiderers of Lumban, Laguna directly with heritage fashion lovers around the world.
                    </p>
                </div>

                <div class="space-y-3">
                    <h3 class="text-base font-bold text-gray-900">Our Mission</h3>
                    <p>
                        LumBarong was founded with a singular purpose: to preserve, empower, and celebrate the intricate art of hand-embroidered Barong Tagalog from Lumban, Laguna—the official <em>Embroidery Capital of the Philippines</em>. We provide local artisans with a modern, direct digital marketplace to showcase and sell their timeless creations worldwide.
                    </p>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 pt-2">
                    <div class="bg-stone-50 p-4 rounded-2xl border border-stone-100">
                        <div class="text-[#C0420A] font-black text-sm mb-1">100% Authentic</div>
                        <div class="text-xs text-gray-500">Handcrafted directly by verified master artisans and ateliers in Lumban.</div>
                    </div>
                    <div class="bg-stone-50 p-4 rounded-2xl border border-stone-100">
                        <div class="text-[#C0420A] font-black text-sm mb-1">Custom Tailoring</div>
                        <div class="text-xs text-gray-500">Tailored custom measurements for a flawless personal fit.</div>
                    </div>
                    <div class="bg-stone-50 p-4 rounded-2xl border border-stone-100">
                        <div class="text-[#C0420A] font-black text-sm mb-1">Direct Artisan Support</div>
                        <div class="text-xs text-gray-500">Every purchase directly empowers local weavers and embroidery households.</div>
                    </div>
                </div>

                <div class="space-y-3 pt-2">
                    <h3 class="text-base font-bold text-gray-900">The Lumban Craftsmanship</h3>
                    <p>
                        Every piece available on LumBarong tells a story. From delicate Piña fibers to elegant Organza, Cocoon, and Jusi fabrics, each embroidery pattern is meticulously stitched by skilled hands using techniques passed down through generations.
                    </p>
                </div>
            </div>

            {{-- TAB 2: PRIVACY POLICY --}}
            <div x-show="currentTab === 'privacy'" class="space-y-6">
                <div class="text-center pb-4 border-b border-gray-100">
                    <h2 class="font-serif text-2xl sm:text-3xl font-bold text-gray-900 mb-1">Privacy Policy</h2>
                    <p class="text-gray-400 text-xs">Compliance with Republic Act No. 10173 (Data Privacy Act of 2012)</p>
                </div>

                <div class="space-y-2">
                    <h3 class="text-sm font-bold text-gray-900">1. Compliance with Data Privacy Act of 2012 (RA 10173)</h3>
                    <p>
                        LumBarong is committed to protecting your personal data in accordance with <strong>Republic Act No. 10173</strong>, also known as the <em>Data Privacy Act of 2012 (DPA)</em>, its Implementing Rules and Regulations (IRR), and issuances of the National Privacy Commission (NPC). As a user of LumBarong, your privacy rights as a Data Subject are strictly respected and safeguarded.
                    </p>
                </div>

                <div class="space-y-2">
                    <h3 class="text-sm font-bold text-gray-900">2. Information We Collect</h3>
                    <p>
                        To facilitate custom tailoring and secure transactions between buyers and Lumban artisans, we collect personal information including full name, email address, contact numbers, delivery addresses, transaction payment proof, and custom body measurements (neck, chest, shoulder, sleeve, waist, full length). For seller registration, verification documents such as government IDs and business permits are securely gathered.
                    </p>
                </div>

                <div class="space-y-2">
                    <h3 class="text-sm font-bold text-gray-900">3. Purpose and Scope of Data Processing</h3>
                    <p>
                        Collected data is processed strictly for fulfilling orders, managing custom tailoring specifications, processing seller payouts, verifying accounts, preventing fraud, and delivering essential notifications. We do not sell or lease your personal information to third-party marketing brokers.
                    </p>
                </div>

                <div class="space-y-2">
                    <h3 class="text-sm font-bold text-gray-900">4. Data Subject Rights Under RA 10173</h3>
                    <ul class="list-disc list-inside space-y-1 pl-1 text-gray-600">
                        <li><strong>Right to be Informed:</strong> Know how your personal data is collected and processed.</li>
                        <li><strong>Right to Access:</strong> Request reasonable access to your personal records.</li>
                        <li><strong>Right to Rectification:</strong> Request correction of inaccurate or outdated information.</li>
                        <li><strong>Right to Erasure or Blocking:</strong> Request deletion or suspension of your account.</li>
                        <li><strong>Right to Data Portability:</strong> Obtain a copy of your personal data.</li>
                    </ul>
                </div>

                <div class="space-y-2">
                    <h3 class="text-sm font-bold text-gray-900">5. Data Security & Storage Controls</h3>
                    <p>
                        We enforce organizational, physical, and technical security measures—including encrypted storage, secure HTTPS protocols, and role-based access control—to shield your personal information from unauthorized access, alteration, or disclosure.
                    </p>
                </div>
            </div>

            {{-- TAB 3: TERMS & CONDITIONS --}}
            <div x-show="currentTab === 'terms'" class="space-y-6">
                <div class="text-center pb-4 border-b border-gray-100">
                    <h2 class="font-serif text-2xl sm:text-3xl font-bold text-gray-900 mb-1">Terms & Conditions</h2>
                    <p class="text-gray-400 text-xs">Guidelines for buyers and artisan sellers</p>
                </div>

                <div class="space-y-2">
                    <h3 class="text-sm font-bold text-gray-900">1. Agreement to Terms</h3>
                    <p>
                        By accessing or using the LumBarong platform, you agree to be bound by these Terms & Conditions. LumBarong provides a digital marketplace connecting buyers with authentic Lumban artisans.
                    </p>
                </div>

                <div class="space-y-2">
                    <h3 class="text-sm font-bold text-gray-900">2. Orders & Custom Tailoring</h3>
                    <p>
                        Barong Tagalog garments crafted to custom size specifications are tailored to the measurements provided by the customer. Customers are encouraged to verify measurements carefully prior to placing orders.
                    </p>
                </div>

                <div class="space-y-2">
                    <h3 class="text-sm font-bold text-gray-900">3. Seller Responsibilities</h3>
                    <p>
                        Artisans and sellers on LumBarong agree to deliver genuine hand-crafted quality products matching listed product specifications.
                    </p>
                </div>

                <div class="space-y-2">
                    <h3 class="text-sm font-bold text-gray-900">4. Returns & Refunds</h3>
                    <p>
                        Damaged or incorrect items may be submitted for return or refund review in accordance with our return guidelines through the customer orders panel.
                    </p>
                </div>
            </div>

        </div>

        <!-- Modal Footer -->
        <div class="px-6 py-4 border-t border-gray-100 bg-gray-50 flex items-center justify-end">
            <button 
                type="button" 
                @click="closeModal()" 
                class="px-6 py-2.5 bg-[#3D2B1F] hover:bg-[#C0420A] text-white text-xs font-bold uppercase tracking-widest rounded-xl transition-all cursor-pointer"
            >
                Close
            </button>
        </div>
    </div>
</div>
