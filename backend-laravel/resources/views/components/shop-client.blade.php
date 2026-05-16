<div x-data="shopClient({ sellerId: '{{ $id }}' })" x-init="init()" class="min-h-screen bg-stone-50 pb-24 font-sans">
    <!-- Back Button -->
    <div class="mx-auto max-w-5xl px-4 sm:px-6 lg:px-8 pt-6 pb-2 flex justify-between items-center">
        <a href="javascript:history.back()" class="flex items-center gap-1.5 text-xs font-bold text-stone-500 hover:text-[#C0420A] transition-colors">
            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path></svg>
            Back to previous
        </a>
        
        <button 
            @click="reportStore"
            class="flex items-center gap-1.5 text-[10px] font-black uppercase tracking-widest text-red-500 hover:text-red-700 transition-colors bg-white px-3 py-1.5 rounded-full border border-red-100 shadow-sm"
        >
            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
            Report Store
        </button>
    </div>

    <!-- Profile Info -->
    <div class="mx-auto max-w-5xl px-4 sm:px-6 lg:px-8 mb-6">
        <div class="bg-white rounded-md shadow-sm border border-stone-200 flex flex-col md:flex-row overflow-hidden">
            
            <!-- Left Side: Dark Banner -->
            <div class="bg-[#1A1A1A] w-full md:w-[380px] p-5 flex flex-col justify-between shrink-0 relative overflow-hidden">
                <div class="absolute inset-0 opacity-[0.03] bg-white mix-blend-overlay"></div>
                <div class="relative z-10 flex gap-4 items-center">
                    <div class="w-[72px] h-[72px] rounded-full border border-white/20 bg-stone-100 overflow-hidden shrink-0 flex items-center justify-center font-serif text-3xl text-stone-400">
                        <template x-if="seller && seller.profilePhoto">
                            <img :src="seller.profilePhoto.startsWith('http') ? seller.profilePhoto : '/storage/' + seller.profilePhoto" class="w-full h-full object-cover" />
                        </template>
                        <template x-if="!seller || !seller.profilePhoto">
                            <span x-text="seller?.shopName?.[0] || 'A'"></span>
                        </template>
                    </div>
                    <div class="text-left text-white">
                        <h1 class="font-serif text-[17px] font-bold leading-tight flex items-center gap-1.5 tracking-wide">
                            <span x-text="seller?.shopName || 'Artisan Workshop'"></span>
                            <template x-if="seller?.isVerified">
                                <svg class="w-3.5 h-3.5 text-[#A1D4B1]" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path></svg>
                            </template>
                        </h1>
                        <div class="text-white/60 text-[11px] mt-1.5 flex items-center gap-1 font-medium tracking-wide">
                            <svg class="w-3 h-3 opacity-80" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path></svg>
                            <span x-text="seller?.location || 'Lumban, Laguna'"></span>
                        </div>
                    </div>
                </div>
                <div class="relative z-10 flex gap-2.5 mt-5 w-full">
                    <button
                        @click="messageSeller"
                        class="flex-1 flex items-center justify-center gap-1.5 border border-white/30 px-3 py-1.5 text-[10px] font-bold uppercase tracking-widest text-white transition-all hover:bg-white/10 rounded-sm"
                    >
                        <svg class="h-3 w-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"></path></svg>
                        Message
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
                     <span class="text-stone-500 font-medium">Established:</span>
                     <span class="text-[#C0420A] font-bold" x-text="seller?.establishedOn || 'April 2026'"></span>
                  </div>
               </div>
            </div>

        </div>
    </div>

    <!-- Collection -->
    <div class="mx-auto max-w-5xl px-4 py-8 sm:px-6 lg:px-8">
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

            <div class="no-scrollbar flex w-full max-w-md gap-2 overflow-x-auto pb-1 text-[10px] sm:text-xs font-bold uppercase tracking-widest justify-center">
                <button
                    @click="activeTab = 'all'"
                    :class="activeTab === 'all' ? 'bg-[#C0420A] text-white shadow-md' : 'bg-white text-stone-500 hover:text-[#C0420A]'"
                    class="shrink-0 rounded-full px-6 py-2.5 transition-colors"
                >
                    All Pieces
                </button>
                <button
                    @click="activeTab = 'sale'"
                    :class="activeTab === 'sale' ? 'bg-[#C0420A] text-white shadow-md' : 'bg-white text-stone-500 hover:text-[#C0420A]'"
                    class="shrink-0 rounded-full px-6 py-2.5 transition-colors"
                >
                    On Sale
                </button>
                <button
                    @click="activeTab = 'rated'"
                    :class="activeTab === 'rated' ? 'bg-[#C0420A] text-white shadow-md' : 'bg-white text-stone-500 hover:text-[#C0420A]'"
                    class="shrink-0 rounded-full px-6 py-2.5 transition-colors"
                >
                    Highest Rated
                </button>
            </div>
        </div>

        <div class="grid grid-cols-2 gap-4 sm:gap-6 md:grid-cols-3 lg:grid-cols-4">
            <template x-for="product in displayedProducts" :key="product.id">
                <a :href="'/products/' + product.id" class="group relative flex flex-col bg-white rounded-sm shadow-sm hover:-translate-y-1 hover:shadow-lg border border-transparent hover:border-[#C0420A] transition-all duration-300">
                    <div class="relative aspect-square overflow-hidden bg-stone-50 rounded-t-sm">
                        <img :src="product.image ? (product.image.startsWith('http') ? product.image : '/storage/' + product.image) : '/images/placeholder.png'" class="w-full h-full object-cover transition-transform duration-700 group-hover:scale-105" />
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
                            <div class="text-sm font-bold text-[#C0420A]" x-text="'₱' + parseFloat(product.price || 0).toLocaleString()"></div>
                            <div class="text-[10px] text-stone-400 uppercase tracking-wider font-bold" x-text="'Sold ' + (product.soldCount || 0)"></div>
                        </div>
                    </div>
                </a>
            </template>
        </div>

        <template x-if="products.length === 0 && !loading">
            <div class="rounded-2xl border-2 border-dashed border-gray-200 px-6 py-16 text-center sm:py-24">
                <p class="font-serif italic text-gray-400">This artisan has not released any collection to the registry yet.</p>
            </div>
        </template>
        
        <template x-if="loading">
            <div class="flex justify-center py-20">
                <svg class="w-8 h-8 animate-spin text-[#C0420A]" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
            </div>
        </template>
    </div>
</div>

<script>
function shopClient(config) {
    return {
        sellerId: config.sellerId,
        seller: null,
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
                const [sRes, pRes] = await Promise.all([
                    fetch(`/api/v1/user/seller/${this.sellerId}`),
                    fetch(`/api/v1/products?seller=${this.sellerId}`)
                ]);
                if (sRes.ok) this.seller = await sRes.json();
                if (pRes.ok) this.products = await pRes.json();
            } catch (e) { console.error(e); }
            this.loading = false;
        },
        get displayedProducts() {
            let p = [...this.products];
            if (this.activeTab === 'rated') {
                p.sort((a, b) => (Number(b.rating) || 0) - (Number(a.rating) || 0));
            } else if (this.activeTab === 'sale') {
                p = p.filter(item => item.onSale); // Assuming onSale property
            }
            
            if (this.searchQuery.trim()) {
                const q = this.searchQuery.toLowerCase();
                p = p.filter(item => (item.name || '').toLowerCase().includes(q) || (item.description || '').toLowerCase().includes(q));
            }
            return p;
        },
        messageSeller() {
            window.location.href = `/messages?sellerId=${this.sellerId}&sellerName=${encodeURIComponent(this.seller?.shopName || 'Artisan')}`;
        },
        reportStore() {
            // Logic for opening report modal or navigating to report page
            alert('Report feature coming soon to this component.');
        }
    }
}
</script>
