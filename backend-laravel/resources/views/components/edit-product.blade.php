<div x-data="editProductComponent()" x-init="init({{ $product->id }})" class="max-w-[1400px] mx-auto space-y-10 relative">
    <!-- Header -->
    <div class="flex items-center gap-4">
        <a href="/seller/products" class="w-10 h-10 rounded-full bg-white border border-gray-100 flex items-center justify-center text-gray-400 hover:text-[#C0420A] transition-all">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path></svg>
        </a>
        <div>
            <div class="text-[10px] font-bold uppercase tracking-widest text-[#C0420A]">Inventory Management</div>
            <h1 class="font-serif text-2xl font-bold tracking-tight text-black">
                Edit <span class="text-[#C0420A] italic lowercase">product</span>
            </h1>
        </div>
    </div>

    <form @submit.prevent="submit" class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        <!-- Media Column -->
        <div class="space-y-8">
            <div class="bg-white p-8 rounded-3xl border border-gray-100 shadow-sm space-y-6">
                <h3 class="text-lg font-bold">Variations & Media</h3>
                
                <div class="space-y-4">
                    <template x-for="(v, index) in variations" :key="index">
                        <div class="flex gap-4 p-4 border border-gray-100 rounded-2xl bg-gray-50/50 relative group">
                            <div class="w-20 h-20 relative rounded-xl overflow-hidden shrink-0 shadow-sm bg-white">
                                <img :src="v.preview" class="w-full h-full object-cover">
                            </div>
                            <div class="flex-1 space-y-1">
                                <label class="text-[9px] font-black uppercase text-gray-400">Label</label>
                                <input type="text" x-model="v.label" class="w-full px-3 py-2 bg-white border border-gray-100 rounded-lg text-sm outline-none focus:border-[#C0420A]" placeholder="e.g. Classic White">
                            </div>
                            <button type="button" @click="removeVariation(index)" class="absolute -top-2 -right-2 p-1.5 bg-red-500 text-white rounded-full opacity-0 group-hover:opacity-100 transition-opacity shadow-lg">
                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                            </button>
                        </div>
                    </template>

                    <div class="grid grid-cols-2 gap-4">
                        <label class="py-8 border-2 border-dashed border-gray-100 rounded-2xl flex flex-col items-center justify-center bg-gray-50/50 hover:bg-white transition-all cursor-pointer group">
                            <svg class="w-6 h-6 text-gray-300 mb-2 group-hover:text-[#C0420A]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2-2v12a2 2 0 002 2z"></path></svg>
                            <span class="text-[9px] font-bold uppercase tracking-widest text-gray-400">Upload</span>
                            <input type="file" multiple accept="image/*" class="hidden" @change="handleImageChange">
                        </label>
                    </div>
                </div>
            </div>
        </div>

        <!-- Info Column -->
        <div class="lg:col-span-2 space-y-8">
            <div class="bg-white p-8 rounded-3xl border border-gray-100 shadow-sm space-y-6">
                <h3 class="text-lg font-bold">General Information</h3>
                <div class="space-y-4">
                    <div class="space-y-2">
                        <label class="text-[10px] font-bold uppercase tracking-widest text-gray-400">Product Name</label>
                        <input type="text" x-model="formData.name" required class="w-full px-5 py-4 bg-gray-50/30 border border-gray-100 rounded-2xl outline-none focus:border-[#C0420A] transition-all font-medium">
                    </div>
                    <div class="space-y-2">
                        <label class="text-[10px] font-bold uppercase tracking-widest text-gray-400">Description</label>
                        <textarea x-model="formData.description" required rows="4" class="w-full px-5 py-4 bg-gray-50/30 border border-gray-100 rounded-2xl outline-none focus:border-[#C0420A] transition-all font-medium resize-none"></textarea>
                    </div>
                    <div class="grid grid-cols-2 gap-6">
                        <div class="space-y-2">
                            <label class="text-[10px] font-bold uppercase tracking-widest text-gray-400">Price (₱)</label>
                            <input type="number" x-model="formData.price" required class="w-full px-5 py-4 bg-gray-50/30 border border-gray-100 rounded-2xl outline-none focus:border-[#C0420A] transition-all font-bold">
                        </div>
                        <div class="space-y-2">
                            <label class="text-[10px] font-bold uppercase tracking-widest text-gray-400">Stock</label>
                            <input type="number" x-model="formData.stock" readonly class="w-full px-5 py-4 bg-gray-50/10 border border-gray-100 rounded-2xl text-[#C0420A] font-bold cursor-not-allowed">
                        </div>
                    </div>
                </div>
            </div>

            <!-- Sizes & Categories -->
            <div class="bg-white p-8 rounded-3xl border border-gray-100 shadow-sm space-y-6">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                    <!-- Sizes -->
                    <div class="space-y-4">
                        <label class="text-[10px] font-bold uppercase tracking-widest text-gray-400">Sizes & Stock</label>
                        <div class="space-y-3">
                            <template x-for="(s, idx) in formData.sizes" :key="idx">
                                <div class="flex items-center gap-3 p-3 bg-gray-50/50 rounded-xl border border-gray-100">
                                    <span class="w-12 text-xs font-bold text-[#C0420A] uppercase" x-text="s.name"></span>
                                    <input type="number" x-model="s.stock" @input="calculateStock" class="flex-1 bg-transparent border-none focus:ring-0 text-sm font-bold text-right">
                                    <button type="button" @click="formData.sizes.splice(idx, 1); calculateStock()" class="text-gray-300 hover:text-red-500 transition-colors">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                                    </button>
                                </div>
                            </template>
                        </div>
                        <div class="flex gap-2">
                            <input type="text" x-model="newSize" placeholder="Add custom size..." class="flex-1 px-4 py-2 bg-gray-50 border border-gray-100 rounded-xl text-xs outline-none focus:border-[#C0420A]">
                            <button type="button" @click="addSize" class="px-4 py-2 bg-black text-white rounded-xl text-[10px] font-bold uppercase tracking-widest hover:bg-[#C0420A] transition-all">Add</button>
                        </div>
                    </div>

                    <!-- Categories -->
                    <div class="space-y-4">
                        <label class="text-[10px] font-bold uppercase tracking-widest text-gray-400">Categories</label>
                        <div class="flex flex-wrap gap-2 min-h-[42px]">
                            <template x-for="cat in formData.categories">
                                <span class="px-3 py-1.5 bg-[#C0420A] text-white rounded-full text-[10px] font-bold flex items-center gap-2 shadow-sm">
                                    <span x-text="cat"></span>
                                    <button type="button" @click="formData.categories = formData.categories.filter(c => c !== cat)" class="hover:bg-white/20 rounded-full transition-colors">
                                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                                    </button>
                                </span>
                            </template>
                        </div>
                        <select @change="if($event.target.value && !formData.categories.includes($event.target.value)) formData.categories.push($event.target.value); $event.target.value = ''" class="w-full px-4 py-3 bg-gray-50 border border-gray-100 rounded-xl text-sm outline-none focus:border-[#C0420A]">
                            <option value="">+ Add Category</option>
                            @foreach($categories as $cat)
                                <option value="{{ $cat->name }}" :disabled="formData.categories.includes('{{ $cat->name }}')">{{ $cat->name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
            </div>

            <button type="submit" :disabled="loading" class="w-full py-5 bg-[#C0420A] text-white rounded-2xl font-bold uppercase tracking-[0.2em] shadow-xl shadow-red-100 hover:bg-black transition-all flex items-center justify-center gap-3 disabled:opacity-50">
                <template x-if="!loading">
                    <div class="flex items-center gap-2">
                        <span>Update Product</span>
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-3m-1 4l-3 3m0 0l-3-3m3 3V4"></path></svg>
                    </div>
                </template>
                <template x-if="loading">
                    <div class="flex items-center gap-2">
                        <svg class="w-5 h-5 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                        <span>Saving...</span>
                    </div>
                </template>
            </button>
        </div>
    </form>
</div>

<script>
function editProductComponent() {
    return {
        id: null,
        loading: false,
        newSize: '',
        formData: {
            name: '',
            description: '',
            price: 0,
            stock: 0,
            categories: [],
            sizes: []
        },
        variations: [],
        init(id) {
            this.id = id;
            this.fetchProduct();
        },
        async fetchProduct() {
            try {
                const res = await fetch(`/api/v1/products/${this.id}`);
                const data = await res.json();
                this.formData = {
                    name: data.name,
                    description: data.description,
                    price: data.price,
                    stock: data.stock,
                    categories: data.categories || [],
                    sizes: typeof data.sizes === 'string' ? JSON.parse(data.sizes) : (data.sizes || [])
                };
                
                let images = [];
                try {
                    images = typeof data.image === 'string' ? JSON.parse(data.image) : (data.image || []);
                } catch(e) { images = [data.image]; }
                
                this.variations = images.map(img => ({
                    preview: typeof img === 'string' ? (img.startsWith('http') ? img : '/uploads/products/' + img) : (img.url || img.preview),
                    label: img.variation || img.label || '',
                    isExisting: true
                }));
            } catch (e) { console.error(e); }
        },
        addSize() {
            if (this.newSize.trim()) {
                this.formData.sizes.push({ name: this.newSize.trim(), stock: 0 });
                this.newSize = '';
            }
        },
        calculateStock() {
            this.formData.stock = this.formData.sizes.reduce((sum, s) => sum + parseInt(s.stock || 0), 0);
        },
        handleImageChange(e) {
            const files = Array.from(e.target.files);
            files.forEach(file => {
                const reader = new FileReader();
                reader.onload = (e) => {
                    this.variations.push({
                        preview: e.target.result,
                        label: '',
                        file: file,
                        isExisting: false
                    });
                };
                reader.readAsDataURL(file);
            });
        },
        removeVariation(idx) {
            this.variations.splice(idx, 1);
        },
        async submit() {
            this.loading = true;
            try {
                const formData = new FormData();
                formData.append('_method', 'PUT');
                formData.append('name', this.formData.name);
                formData.append('description', this.formData.description);
                formData.append('price', this.formData.price);
                formData.append('stock', this.formData.stock);
                formData.append('categories', JSON.stringify(this.formData.categories));
                formData.append('sizes', JSON.stringify(this.formData.sizes));

                const existingImages = this.variations.filter(v => v.isExisting).map(v => ({ url: v.preview, variation: v.label }));
                formData.append('existingImages', JSON.stringify(existingImages));

                this.variations.filter(v => !v.isExisting).forEach(v => {
                    formData.append('images[]', v.file);
                    formData.append('variationNames[]', v.label);
                });

                const res = await fetch(`/api/v1/products/${this.id}`, {
                    method: 'POST',
                    headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content },
                    body: formData
                });

                if (res.ok) {
                    alert('Product updated successfully');
                    window.location.href = '/seller/products';
                } else {
                    const err = await res.json();
                    alert(err.message || 'Update failed');
                }
            } catch (e) { console.error(e); alert('An error occurred'); }
            this.loading = false;
        }
    }
}
</script>
