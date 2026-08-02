<div x-data="productDetail({ productId: '{{ $id }}' })" x-init="init()" class="min-h-screen bg-[#FAFAFA] pb-24 font-sans text-black">
    <div class="max-w-[1440px] mx-auto px-4 lg:px-12 pt-8">
        
        <!-- Breadcrumbs (Simplified) -->
        <nav class="flex items-center gap-3 text-sm text-gray-400 font-medium mb-8">
            <a href="/" class="hover:text-black transition-colors">Home</a>
            <svg class="w-4 h-4 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path></svg>
            <span class="text-black" x-text="product?.name"></span>
        </nav>

        <!-- Main Product Layout -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-12 lg:gap-20 mb-20">
            
            <!-- Left: Image Gallery -->
            <div class="flex flex-col gap-6">
                <div 
                    class="relative aspect-[4/5] bg-gray-50 rounded-4xl overflow-hidden cursor-zoom-in border border-gray-100" 
                    @click="isZoomOpen = true"
                >
                    <template x-if="galleryImages[activeImage]">
                        <img :src="galleryImages[activeImage].url || galleryImages[activeImage]" class="object-cover w-full h-full" />
                    </template>
                </div>
                
                <div class="flex gap-4 overflow-x-auto pb-2 scrollbar-hide snap-x">
                    <template x-for="(img, i) in galleryImages" :key="i">
                        <button
                            @click="activeImage = i"
                            :class="activeImage === i ? 'border-black opacity-100 shadow-md scale-100' : 'border-transparent opacity-60 hover:opacity-100 scale-95'"
                            class="relative w-24 h-28 rounded-2xl overflow-hidden shrink-0 transition-all border-2 snap-start"
                        >
                            <img :src="img.url || img" class="w-full h-full object-cover" />
                        </button>
                    </template>
                </div>
            </div>

            <!-- Right: Product Details -->
            <div class="flex flex-col pt-2 md:pt-6">
                <span class="text-sm font-semibold text-gray-500 mb-3 uppercase tracking-wider" x-text="product?.category"></span>
                <h1 class="text-3xl md:text-5xl font-extrabold text-black mb-6 leading-[1.1] tracking-tight" x-text="product?.name"></h1>

                <div class="flex items-end gap-6 mb-8">
                    <span class="text-3xl font-extrabold text-black" x-text="'₱' + parseFloat(product?.price || 0).toLocaleString()"></span>
                </div>

                <!-- Size Selector -->
                <div class="mb-10">
                    <div class="flex items-center justify-between mb-5">
                        <span class="text-base font-bold text-black uppercase tracking-wider">Select Size</span>
                        <button @click="showSizeGuide = true" class="text-sm font-bold text-gray-400 hover:text-black transition-colors underline underline-offset-4">
                            Size Guide
                        </button>
                    </div>
                    
                    <div class="flex flex-wrap gap-4">
                        <template x-for="s in availableSizes">
                            <button
                                @click="selectedSize = s"
                                :class="selectedSize === s ? 'bg-black text-white shadow-lg shadow-black/20 scale-105' : 'bg-white text-gray-600 border border-gray-200 hover:border-black'"
                                class="relative w-16 h-16 rounded-full flex items-center justify-center text-sm font-bold transition-all"
                                x-text="s"
                            ></button>
                        </template>
                    </div>
                </div>

                <!-- Quantity -->
                <div class="flex items-center gap-6 mb-10">
                    <div class="flex items-center border-2 border-gray-100 rounded-full bg-white h-[60px] w-40 overflow-hidden">
                        <button 
                            class="w-12 h-full flex items-center justify-center text-gray-400 hover:text-black hover:bg-gray-50 transition-colors"
                            @click="quantity = Math.max(1, quantity - 1)"
                        >
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 12H4"></path></svg>
                        </button>
                        <input
                            type="number"
                            x-model="quantity"
                            class="flex-1 text-center font-bold text-lg text-black bg-transparent outline-none h-full w-full"
                        />
                        <button 
                            class="w-12 h-full flex items-center justify-center text-gray-400 hover:text-black hover:bg-gray-50 transition-colors"
                            @click="quantity++"
                        >
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
                        </button>
                    </div>
                    <span class="text-sm font-medium text-gray-400" x-text="product?.stock + ' items left'"></span>
                </div>

                <!-- Actions -->
                <div class="flex flex-col gap-3 mb-12">
                    <button 
                        @click="addToCart" 
                        class="w-full h-[64px] bg-white border-2 border-black text-black rounded-2xl font-bold text-sm uppercase tracking-widest hover:bg-gray-50 transition-colors flex items-center justify-center gap-3 active:scale-[0.98]"
                    >
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"></path></svg>
                        <span x-text="addedToCart ? 'Added!' : 'Add to Cart'"></span>
                    </button>
                    
                    <button 
                        @click="buyNow" 
                        class="w-full h-[64px] bg-black text-white rounded-2xl font-bold text-sm uppercase tracking-widest hover:bg-gray-900 transition-all shadow-xl shadow-black/10 flex items-center justify-center gap-3 active:scale-[0.98]"
                    >
                        Buy It Now
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function productDetail(config) {
    return {
        productId: config.productId,
        product: null,
        loading: true,
        activeImage: 0,
        selectedSize: null,
        quantity: 1,
        addedToCart: false,
        galleryImages: [],
        availableSizes: [],
        init() {
            this.fetchProduct();
        },
        async fetchProduct() {
            this.loading = true;
            try {
                const res = await fetch(`/api/v1/products/${this.productId}`);
                if (res.ok) {
                    this.product = await res.json();
                    this.galleryImages = this.normalizeImages(this.product.image);
                    this.availableSizes = this.normalizeSizes(this.product.sizes);
                }
            } catch (e) { console.error(e); }
            this.loading = false;
        },
        normalizeImages(img) {
            if (!img) return [{url: '/images/placeholder.png'}];
            let images = [];
            if (typeof img === 'string') {
                try {
                    const parsed = JSON.parse(img);
                    images = Array.isArray(parsed) ? parsed : [parsed];
                } catch(e) { images = [img]; }
            } else {
                images = Array.isArray(img) ? img : [img];
            }
            return images.map(i => {
                const url = typeof i === 'string' ? i : (i.url || i);
                return { url: url.startsWith('http') ? url : '/storage/' + url };
            });
        },
        normalizeSizes(sizes) {
            if (!sizes) return ["S", "M", "L", "XL"];
            if (typeof sizes === 'string') {
                try {
                    const parsed = JSON.parse(sizes);
                    return Array.isArray(parsed) ? parsed : [parsed];
                } catch(e) { return [sizes]; }
            }
            return Array.isArray(sizes) ? sizes : [sizes];
        },
        addToCart() {
            if (!this.selectedSize) { alert('Please select a size first.'); return; }
            this.addedToCart = true;
            setTimeout(() => this.addedToCart = false, 2000);
            
            // Logic for adding to cart (localStorage or API)
            const cart = JSON.parse(localStorage.getItem('cart') || '[]');
            cart.push({
                id: this.product.id,
                name: this.product.name,
                price: this.product.price,
                size: this.selectedSize,
                qty: this.quantity,
                image: this.galleryImages[0].url
            });
            localStorage.setItem('cart', JSON.stringify(cart));
            window.dispatchEvent(new CustomEvent('cart-updated'));
        },
        buyNow() {
            if (!this.selectedSize) { alert('Please select a size first.'); return; }
            // Logic for direct checkout
            window.location.href = `/checkout?productId=${this.productId}&size=${this.selectedSize}&qty=${this.quantity}`;
        }
    }
}
</script>
