@props(['id'])

<div x-data="shopClient({ sellerId: '{{ $id }}', isLoggedIn: {{ auth()->check() ? 'true' : 'false' }} })" x-init="init()" class="min-h-screen bg-stone-50 pb-24 font-sans">
    <!-- Back Button -->
    <div class="mx-auto max-w-5xl px-4 sm:px-6 lg:px-8 pt-6 pb-2 flex justify-between items-center">
        <a href="javascript:history.back()" class="flex items-center gap-1.5 text-xs font-bold text-stone-500 hover:text-[#C0420A] transition-colors">
            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path></svg>
            Back to previous
        </a>
        
        @if(!Auth::check() || Auth::id() !== $id)
        <button 
            @click="reportStore"
            class="flex items-center gap-1.5 text-[10px] font-black uppercase tracking-widest text-red-500 hover:text-red-700 transition-colors bg-white px-3 py-1.5 rounded-full border border-red-100 shadow-sm"
        >
            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
            Report Store
        </button>
        @endif
    </div>

    <!-- Profile Info -->
    <div class="mx-auto max-w-5xl px-4 sm:px-6 lg:px-8 mb-6">
        <div class="bg-white rounded-md shadow-sm border border-stone-200 flex flex-col md:flex-row overflow-hidden">
            
            <!-- Left Side: Dark/Gold Premium Banner -->
            <div class="w-full md:w-95 p-5 flex flex-col justify-between shrink-0 relative overflow-hidden transition-all duration-500"
                 :class="seller?.isPremium ? 'border-r border-yellow-500/10' : 'bg-[#1A1A1A]'"
                 :style="seller?.isPremium ? 'background: linear-gradient(to bottom, #2E2A24, #1A1A1A);' : ''">
                <div class="absolute inset-0 opacity-[0.03] bg-white mix-blend-overlay"></div>
                <div class="relative z-10 flex gap-4 items-center">
                    <div class="w-18 h-18 rounded-full border border-white/20 bg-stone-100 overflow-hidden shrink-0 flex items-center justify-center font-serif text-3xl text-stone-400">
                        <template x-if="seller && seller.profilePhoto">
                            <img :src="getProductImage(seller.profilePhoto)" class="w-full h-full object-cover" x-on:error="$event.target.src='/uploads/products/default.jpg'" />
                        </template>
                        <template x-if="!seller || !seller.profilePhoto">
                            <span x-text="seller?.shopName?.[0] || 'A'"></span>
                        </template>
                    </div>
                    <div class="text-left text-white">
                        <h1 class="font-serif text-[17px] font-bold leading-tight flex items-center gap-1.5 tracking-wide flex-wrap">
                            <span x-text="seller?.shopName || 'Artisan Workshop'"></span>
                            <template x-if="seller?.isVerified">
                                <svg class="w-3.5 h-3.5 text-[#A1D4B1]" fill="currentColor" viewBox="0 0 20 20" title="Verified Store"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path></svg>
                            </template>
                            <template x-if="seller?.isPremium">
                                <span class="inline-flex items-center gap-1 px-2 py-0.5 bg-yellow-500/20 border border-yellow-500/30 text-yellow-400 text-[8px] font-bold uppercase tracking-wider rounded-full" title="Premium Artisan">
                                    👑 Premium
                                </span>
                            </template>
                        </h1>
                        <div class="text-white/60 text-[11px] mt-1.5 flex items-center gap-1 font-medium tracking-wide">
                            <svg class="w-3 h-3 opacity-80" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path></svg>
                            <span x-text="seller?.location || 'Lumban, Laguna'"></span>
                        </div>
                    </div>
                </div>
                <div class="relative z-10 flex gap-2 mt-5 w-full">
                    @if(!Auth::check() || Auth::id() !== $id)
                    <button
                        @click="messageSeller"
                        class="flex-1 flex items-center justify-center gap-1.5 border border-white/30 px-3 py-1.5 text-[10px] font-bold uppercase tracking-widest text-white transition-all hover:bg-white/10 rounded-sm cursor-pointer"
                    >
                        <svg class="h-3 w-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"></path></svg>
                        Message
                    </button>
                    @endif
                    <button
                        @click="showPolicyModal = true"
                        class="flex-1 flex items-center justify-center gap-1.5 border border-white/30 px-3 py-1.5 text-[10px] font-bold uppercase tracking-widest text-white transition-all hover:bg-white/10 rounded-sm cursor-pointer"
                    >
                        <svg class="h-3 w-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                        Policies
                    </button>
                </div>
            </div>

            <!-- Right Side: Stats -->
            <div class="flex-1 p-5 sm:p-6 md:p-8 flex items-center bg-white">
               <div class="grid grid-cols-1 sm:grid-cols-2 gap-y-6 gap-x-8 w-full">
                  <div class="flex items-center gap-3 text-sm">
                     <span class="text-stone-500 font-medium">Masterpieces:</span>
                     <span class="text-[#C0420A] font-bold" x-text="products.length"></span>
                  </div>
                  <div class="flex items-center gap-3 text-sm">
                     <span class="text-stone-500 font-medium">Rating:</span>
                     <span class="text-[#C0420A] font-bold" x-text="Number(seller?.rating || 0).toFixed(1)"></span>
                  </div>
                  <div class="flex items-center gap-3 text-sm">
                     <span class="text-stone-500 font-medium">Response:</span>
                     <span class="text-[#C0420A] font-bold" x-text="seller?.responseRate || '100%'"></span>
                  </div>
                  <div class="flex items-center gap-3 text-sm">
                     <span class="text-stone-500 font-medium">Joined:</span>
                     <span class="text-[#C0420A] font-bold" x-text="seller?.joined || 'April 2026'"></span>
                  </div>
               </div>
            </div>

        </div>
    </div>

    <!-- Collection -->
    <div id="shop-catalogue" class="mx-auto max-w-5xl px-4 py-8 sm:px-6 lg:px-8">
        <div class="mb-6 flex flex-col items-center text-center">
            <h3 class="font-serif text-xl sm:text-2xl font-bold tracking-tight text-black mb-4">
                The <span class="text-[#C0420A] italic">Collection</span>
            </h3>

            <div class="w-full max-w-md relative mb-6">
                <input
                    type="text"
                    placeholder="Search in this shop..."
                    x-model="searchQuery"
                    class="w-full bg-white border border-stone-200 rounded-full py-2.5 pl-11 pr-4 text-sm font-medium text-stone-700 outline-none focus:border-[#C0420A] transition-colors shadow-sm"
                />
                <svg class="absolute left-4 top-1/2 -translate-y-1/2 w-4 h-4 text-stone-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
            </div>

            <div class="no-scrollbar flex w-full max-w-xl gap-2 overflow-x-auto pb-1 text-[10px] sm:text-xs font-bold uppercase tracking-widest justify-center">
                <button
                    @click="activeTab = 'all'"
                    :class="activeTab === 'all' ? 'bg-[#C0420A] text-white shadow-md' : 'bg-white text-stone-500 hover:text-[#C0420A]'"
                    class="shrink-0 rounded-full px-5 py-2.5 transition-colors cursor-pointer"
                >
                    All Pieces
                </button>
                <button
                    @click="activeTab = 'sale'"
                    :class="activeTab === 'sale' ? 'bg-[#C0420A] text-white shadow-md' : 'bg-white text-stone-500 hover:text-[#C0420A]'"
                    class="shrink-0 rounded-full px-5 py-2.5 transition-colors cursor-pointer"
                >
                    On Sale
                </button>
                <button
                    @click="activeTab = 'rated'"
                    :class="activeTab === 'rated' ? 'bg-[#C0420A] text-white shadow-md' : 'bg-white text-stone-500 hover:text-[#C0420A]'"
                    class="shrink-0 rounded-full px-5 py-2.5 transition-colors cursor-pointer"
                >
                    Highest Rated
                </button>
                <button
                    @click="activeTab = 'policies'"
                    :class="activeTab === 'policies' ? 'bg-[#C0420A] text-white shadow-md' : 'bg-white text-stone-500 hover:text-[#C0420A]'"
                    class="shrink-0 rounded-full px-5 py-2.5 transition-colors cursor-pointer flex items-center gap-1.5"
                >
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                    <span>Shop Policies</span>
                </button>
            </div>
        </div>

        {{-- Dedicated Policies Tab Content --}}
        <div x-show="activeTab === 'policies'" class="max-w-3xl mx-auto space-y-6 animate-fade-in" x-cloak>
            <div class="bg-white rounded-3xl p-6 sm:p-8 border border-stone-200 shadow-sm space-y-6">
                <div class="border-b border-stone-100 pb-4">
                    <div class="flex items-center gap-2 text-[#C0420A] text-xs font-black uppercase tracking-widest mb-1">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>
                        Artisan Trust & Storefront Policies
                    </div>
                    <h3 class="font-serif text-2xl font-bold text-black" x-text="`${seller?.shopName || 'Shop'} Terms & Guarantees`"></h3>
                    <p class="text-xs text-stone-500 mt-1">Please review these terms before placing an order. These policies apply to all handcrafted orders purchased from this shop.</p>
                </div>

                {{-- Cancellation Policy --}}
                <div class="p-5 sm:p-6 bg-amber-50/70 border border-amber-200/80 rounded-2xl space-y-2">
                    <div class="flex items-center gap-2 text-xs font-bold text-amber-900 uppercase tracking-wider">
                        <div class="w-7 h-7 rounded-lg bg-amber-200/80 flex items-center justify-center text-amber-800 shrink-0">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        </div>
                        <span>Cancellation Policy</span>
                    </div>
                    <p class="text-xs sm:text-sm text-stone-700 leading-relaxed pl-9 font-medium" x-text="seller?.cancellation_policy || 'Cancellation requests must be submitted prior to order processing and payment verification. Once payment is confirmed and artisan crafting begins, cancellations may not be accepted.'"></p>
                </div>

                {{-- Refund & Return Policy --}}
                <div class="p-5 sm:p-6 bg-blue-50/70 border border-blue-200/80 rounded-2xl space-y-2">
                    <div class="flex items-center gap-2 text-xs font-bold text-blue-900 uppercase tracking-wider">
                        <div class="w-7 h-7 rounded-lg bg-blue-200/80 flex items-center justify-center text-blue-800 shrink-0">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        </div>
                        <span>Refund & Return Policy</span>
                    </div>
                    <p class="text-xs sm:text-sm text-stone-700 leading-relaxed pl-9 font-medium" x-text="seller?.refund_policy || 'Refund requests are subject to shop evaluation. Custom tailored garments are crafted to provided measurements. Damaged or defective items upon arrival may be submitted for review through our return system.'"></p>
                </div>

                <div class="pt-2 flex justify-center">
                    <button type="button" @click="activeTab = 'all'" class="px-6 py-2.5 bg-stone-900 hover:bg-[#C0420A] text-white rounded-full text-xs font-bold uppercase tracking-widest transition-all cursor-pointer shadow-sm">
                        Browse Shop Collection
                    </button>
                </div>
            </div>
        </div>

        {{-- Products Grid (shown when not on policies tab) --}}
        <div x-show="activeTab !== 'policies'" class="grid grid-cols-2 gap-4 sm:gap-6 md:grid-cols-3 lg:grid-cols-4">
            <template x-for="product in displayedProducts" :key="product.id">
                <a :href="'/products/' + product.id" class="group relative flex flex-col bg-white rounded-sm shadow-sm hover:-translate-y-1 hover:shadow-lg border border-transparent hover:border-[#C0420A] transition-all duration-300">
                    <div class="relative aspect-square overflow-hidden bg-stone-50 rounded-t-sm">
                        <img :src="getProductImage(product.image)" class="w-full h-full object-cover transition-transform duration-700 group-hover:scale-105" x-on:error="$event.target.src='/uploads/products/default.jpg'" />
                        <template x-if="product.is_on_sale && parseFloat(product.discount_percentage || 0) > 0">
                            <div class="absolute top-1.5 left-1.5 sm:top-2 sm:left-2 flex flex-col gap-1 sm:gap-1.5 z-10 pointer-events-none max-w-[calc(100%-12px)]">
                                <div class="inline-flex items-center gap-1 sm:gap-1.5 px-1.5 py-0.5 sm:px-2.5 sm:py-1 rounded-full border border-[#A87B10] shadow-[0_0_8px_rgba(180,130,15,0.45),inset_0_1px_0_rgba(230,185,60,0.12)] bg-gradient-to-br from-[#0F0C08] to-[#1C1609] whitespace-nowrap self-start max-w-full">
                                    <img src="/images/logo-icon.png" alt="LumBarong" class="w-2.5 h-2.5 sm:w-3.5 sm:h-3.5 rounded-full shrink-0 object-cover">
                                    <span class="text-[#DFC97A] text-[6.5px] sm:text-[8px] font-bold tracking-tight sm:tracking-wider uppercase truncate">Lumban Special</span>
                                </div>
                                <div class="inline-flex items-baseline px-1.5 py-0.5 sm:px-2.5 sm:py-1 rounded-full border border-[#5C3E04] shadow-[0_2px_10px_rgba(200,137,10,0.5),inset_0_1px_0_rgba(255,220,80,0.25)] bg-gradient-to-r from-[#7A5505] via-[#C8890A] to-[#7A5505] whitespace-nowrap self-start">
                                    <span class="text-[#FFF8E0] text-[10px] sm:text-[13px] md:text-[15px] font-black leading-none tracking-tight" x-text="'-' + Math.round(product.discount_percentage) + '%'"></span>
                                    <span class="text-[#FFE8A0] text-[6px] sm:text-[8px] md:text-[9px] font-bold uppercase tracking-wider ml-0.5 sm:ml-1">OFF</span>
                                </div>
                            </div>
                        </template>
                    </div>
                    <div class="flex flex-1 flex-col justify-between p-3 sm:p-4">
                        <div>
                            <h4 class="mb-1.5 line-clamp-2 text-[13px] font-medium leading-tight text-[#222] transition-colors group-hover:text-[#C0420A]" x-text="product.name"></h4>
                            <div class="flex items-center gap-1.5 mb-2">
                                <svg class="w-3 h-3 text-yellow-400 fill-current" viewBox="0 0 20 20"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"></path></svg>
                                <span class="text-[10px] font-medium text-stone-500" x-text="Number(product.rating || 0).toFixed(1)"></span>
                            </div>
                        </div>
                        <div class="flex items-end justify-between gap-3 mt-1">
                            <div>
                                <template x-if="product.is_on_sale && parseFloat(product.discount_percentage || 0) > 0">
                                    <div class="flex items-center gap-1.5 flex-wrap">
                                        <span class="text-sm font-bold text-[#C0420A]" x-text="'₱' + parseFloat(product.price * (1 - product.discount_percentage / 100)).toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2})"></span>
                                        <span class="text-[10px] text-gray-400 line-through font-medium" x-text="'₱' + parseFloat(product.price).toLocaleString()"></span>
                                    </div>
                                </template>
                                <template x-if="!(product.is_on_sale && parseFloat(product.discount_percentage || 0) > 0)">
                                    <span class="text-sm font-bold text-[#C0420A]" x-text="'₱' + parseFloat(product.price || 0).toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2})"></span>
                                </template>
                            </div>
                            <div class="text-[10px] text-stone-400 uppercase tracking-wider font-bold shrink-0 pb-0.5" x-text="'Sold ' + (product.soldCount || 0)"></div>
                        </div>
                    </div>
                </a>
            </template>
        </div>

        <template x-if="activeTab !== 'policies' && products.length === 0 && !loading">
            <div class="rounded-2xl border-2 border-dashed border-gray-200 px-6 py-16 text-center sm:py-24">
                <p class="font-serif italic text-gray-400">This artisan has not released any collection to the registry yet.</p>
            </div>
        </template>
        
        <template x-if="activeTab !== 'policies' && loading">
            <div class="flex justify-center py-20">
                <svg class="w-8 h-8 animate-spin text-[#C0420A]" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
            </div>
        </template>
    </div>

    <!-- Shop Policies Modal -->
    <div x-show="showPolicyModal" class="fixed inset-0 z-110 flex items-center justify-center p-4" style="display: none;" x-cloak>
        <div class="absolute inset-0 bg-black/60 backdrop-blur-sm" @click="showPolicyModal = false"></div>
        <div @click.away="showPolicyModal = false" class="relative bg-white w-full max-w-lg rounded-3xl shadow-2xl p-6 sm:p-8 space-y-5" x-transition>
            <div class="flex items-center justify-between border-b border-gray-100 pb-3">
                <div class="flex items-center gap-2">
                    <div class="w-3 h-3 rounded-full bg-[#C0420A]"></div>
                    <h3 class="font-serif text-lg sm:text-xl font-bold text-black flex items-center gap-2">
                        <span x-text="seller?.shopName || 'Shop'"></span> Policies
                    </h3>
                </div>
                <button @click="showPolicyModal = false" class="text-gray-400 hover:text-gray-600 transition-colors cursor-pointer p-1">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>

            <div class="space-y-4 text-left">
                <!-- Cancellation Policy -->
                <div class="p-4 bg-amber-50/70 border border-amber-200/70 rounded-2xl space-y-1.5">
                    <div class="flex items-center gap-1.5 text-xs font-bold text-amber-900 uppercase tracking-wider">
                        <svg class="w-4 h-4 text-amber-700" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        <span>Cancellation Policy</span>
                    </div>
                    <p class="text-xs text-amber-950 leading-relaxed" x-text="seller?.cancellation_policy || 'Cancellation requests must be submitted prior to order processing and payment verification. Once payment is confirmed and artisan crafting begins, cancellations may not be accepted.'"></p>
                </div>

                <!-- Refund & Return Policy -->
                <div class="p-4 bg-blue-50/70 border border-blue-200/70 rounded-2xl space-y-1.5">
                    <div class="flex items-center gap-1.5 text-xs font-bold text-blue-900 uppercase tracking-wider">
                        <svg class="w-4 h-4 text-blue-700" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        <span>Refund & Return Policy</span>
                    </div>
                    <p class="text-xs text-blue-950 leading-relaxed" x-text="seller?.refund_policy || 'Refund requests are subject to shop evaluation. Custom tailored garments are crafted to provided measurements. Damaged or defective items upon arrival may be submitted for review through our return system.'"></p>
                </div>
            </div>

            <div class="pt-2 text-right">
                <button type="button" @click="showPolicyModal = false" class="w-full py-3 rounded-xl bg-gray-900 text-white text-xs font-bold uppercase tracking-widest hover:bg-[#C0420A] transition-all cursor-pointer">
                    Close
                </button>
            </div>
        </div>
    </div>
</div>

<script>
function shopClient(config) {
    return {
        sellerId: config.sellerId,
        isLoggedIn: config.isLoggedIn,
        seller: null,
        showPolicyModal: false,
        products: [],
        loading: true,
        activeTab: 'all',
        searchQuery: '',
        init() {
            this.fetchData();
        },
        async fetchData() {
            this.loading = true;
            try {
                const ts = Date.now();
                const [sRes, pRes] = await Promise.all([
                    fetch(`/api/v1/user/seller/${this.sellerId}?t=${ts}`, { cache: 'no-store' }),
                    fetch(`/api/v1/products?seller=${this.sellerId}&t=${ts}`, { cache: 'no-store' })
                ]);
                if (sRes.ok) this.seller = await sRes.json();
                if (pRes.ok) this.products = await pRes.json();
            } catch (e) { console.error(e); }
            this.loading = false;
        },
        getProductImage(img) {
            if (!img) return '/uploads/products/default.jpg';
            let path = '';
            if (Array.isArray(img)) {
                path = img.length > 0 ? img[0] : '';
            } else if (typeof img === 'string') {
                path = img;
            }
            if (!path) return '/uploads/products/default.jpg';
            if (path.startsWith('http')) return path;
            if (path.startsWith('/storage/')) return path;
            if (path.startsWith('storage/')) return '/' + path;
            if (path.startsWith('/uploads/')) return path;
            if (path.startsWith('uploads/')) return '/' + path;
            return '/storage/' + path.replace(/^\//, '');
        },
        get displayedProducts() {
            let p = [...this.products];
            if (this.activeTab === 'rated') {
                p.sort((a, b) => (Number(b.rating) || 0) - (Number(a.rating) || 0));
            } else if (this.activeTab === 'sale') {
                p = p.filter(item => item.is_on_sale); // Reference actual database property name
            }
            
            if (this.searchQuery.trim()) {
                const q = this.searchQuery.toLowerCase();
                p = p.filter(item => (item.name || '').toLowerCase().includes(q) || (item.description || '').toLowerCase().includes(q));
            }
            return p;
        },
        messageSeller() {
            if (this.isLoggedIn) {
                window.dispatchEvent(new CustomEvent('open-chat', { 
                    detail: { 
                        sellerId: this.sellerId, 
                        sellerName: this.seller?.shopName || 'Artisan' 
                    } 
                }));
            } else {
                const intent = {
                    action: 'chat',
                    sellerId: this.sellerId,
                    sellerName: this.seller?.shopName || 'Artisan',
                    redirectUrl: window.location.href
                };
                try { localStorage.setItem('lumbarong_pending_intent', JSON.stringify(intent)); } catch(e) {}
                window.location.href = '/login';
            }
        },
        reportStore() {
            if (this.isLoggedIn) {
                window.dispatchEvent(new CustomEvent('open-report', { 
                    detail: { 
                        reportedId: this.sellerId, 
                        reportedName: this.seller?.shopName || 'Artisan', 
                        type: 'CustomerReportingSeller' 
                    } 
                }));
            } else {
                window.dispatchEvent(new CustomEvent('open-auth-gate', { 
                    detail: { message: 'Please log in to report a store.' } 
                }));
            }
        }
    }
}
</script>
