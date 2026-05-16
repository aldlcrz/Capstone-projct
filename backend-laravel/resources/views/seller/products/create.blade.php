@extends('layouts.seller')

@section('content')
<div class="max-w-[1400px] mx-auto">
    <div class="mb-10">
        <a href="{{ route('seller.products.index') }}" class="inline-flex items-center gap-2 text-[10px] font-bold text-gray-400 uppercase tracking-widest hover:text-[#C0420A] transition-colors mb-4">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path></svg>
            Back to catalogue
        </a>
        <h1 class="font-serif text-3xl font-bold text-black uppercase">List New <span class="text-[#C0420A] italic lowercase">heritage piece</span></h1>
    </div>

    <!-- We'll use the edit-product component but modified for "Add" if needed, 
         or just build the form directly for maximum control here. 
         Actually, let's build a dedicated Add form that matches the premium design. -->

    <form action="{{ route('seller.products.store') }}" method="POST" enctype="multipart/form-data" class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        @csrf
        <div class="lg:col-span-2 space-y-8">
            <div class="bg-white p-8 rounded-3xl border border-gray-100 shadow-sm space-y-8">
                <div class="space-y-4">
                    <label class="text-[10px] font-bold uppercase tracking-widest text-gray-400">Product Name</label>
                    <input type="text" name="name" required placeholder="e.g. Pina-Silk Formal Barong Tagalog" class="w-full px-6 py-4 bg-gray-50/50 border border-gray-100 rounded-2xl outline-none focus:border-[#C0420A] transition-all font-medium text-lg">
                </div>

                <div class="space-y-4">
                    <label class="text-[10px] font-bold uppercase tracking-widest text-gray-400">Artisan Description</label>
                    <textarea name="description" required rows="6" placeholder="Describe the craftsmanship, materials used, and the story behind this piece..." class="w-full px-6 py-4 bg-gray-50/50 border border-gray-100 rounded-2xl outline-none focus:border-[#C0420A] transition-all font-medium resize-none"></textarea>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                    <div class="space-y-4">
                        <label class="text-[10px] font-bold uppercase tracking-widest text-gray-400">Price (₱)</label>
                        <input type="number" name="price" required min="1" step="0.01" placeholder="0.00" class="w-full px-6 py-4 bg-gray-50/50 border border-gray-100 rounded-2xl outline-none focus:border-[#C0420A] transition-all font-bold text-xl">
                    </div>
                    <div class="space-y-4">
                        <label class="text-[10px] font-bold uppercase tracking-widest text-gray-400">Initial Stock</label>
                        <input type="number" name="stock" required min="0" placeholder="0" class="w-full px-6 py-4 bg-gray-50/50 border border-gray-100 rounded-2xl outline-none focus:border-[#C0420A] transition-all font-bold text-xl">
                    </div>
                </div>

                <div class="space-y-4">
                    <label class="text-[10px] font-bold uppercase tracking-widest text-gray-400">Product Category</label>
                    <select name="CategoryId" required class="w-full px-6 py-4 bg-gray-50/50 border border-gray-100 rounded-2xl outline-none focus:border-[#C0420A] transition-all font-bold text-sm appearance-none">
                        <option value="" disabled selected>Select a category</option>
                        @foreach($categories as $category)
                            <option value="{{ $category->id }}">{{ $category->name }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div class="bg-white p-8 rounded-3xl border border-gray-100 shadow-sm space-y-6">
                <h3 class="text-sm font-bold text-black uppercase tracking-widest">Heritage Sizing</h3>
                <div class="flex flex-wrap gap-3">
                    @foreach(['S', 'M', 'L', 'XL', 'XXL', 'Custom'] as $size)
                        <label class="cursor-pointer group">
                            <input type="checkbox" name="sizes[]" value="{{ $size }}" class="hidden peer">
                            <div class="px-6 py-3 rounded-xl border border-gray-100 bg-gray-50/50 text-xs font-bold text-gray-400 peer-checked:bg-[#C0420A] peer-checked:text-white peer-checked:border-[#C0420A] peer-checked:shadow-lg peer-checked:shadow-[#C0420A]/20 transition-all">
                                {{ $size }}
                            </div>
                        </label>
                    @endforeach
                </div>
            </div>
        </div>

        <div class="space-y-8">
            <div class="bg-white p-8 rounded-3xl border border-gray-100 shadow-sm space-y-6">
                <h3 class="text-sm font-bold text-black uppercase tracking-widest">Product Imagery</h3>
                <div class="space-y-4">
                    <div class="aspect-3/4 rounded-2xl border-2 border-dashed border-gray-100 bg-gray-50/50 flex flex-col items-center justify-center p-8 text-center group hover:bg-white hover:border-[#C0420A] transition-all cursor-pointer relative overflow-hidden">
                        <input type="file" name="images[]" multiple required class="absolute inset-0 opacity-0 cursor-pointer" onchange="previewImages(this)">
                        <div id="upload-placeholder" class="space-y-2">
                            <svg class="w-10 h-10 text-gray-200 mx-auto group-hover:text-[#C0420A] transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                            <div class="text-[10px] font-bold text-gray-400 uppercase tracking-widest">Upload Heritage Photos</div>
                            <p class="text-[9px] text-gray-300 leading-tight">High resolution portrait shots recommended.</p>
                        </div>
                        <div id="image-preview-container" class="hidden grid-cols-2 gap-2 w-full h-full p-2 bg-white absolute inset-0 overflow-y-auto no-scrollbar"></div>
                    </div>
                </div>
            </div>

            <div class="bg-white p-8 rounded-3xl border border-gray-100 shadow-sm space-y-6">
                <h3 class="text-sm font-bold text-black uppercase tracking-widest">Listing Status</h3>
                <div class="p-4 rounded-2xl bg-amber-50 border border-amber-100">
                    <p class="text-[10px] text-amber-800 leading-relaxed font-bold italic uppercase tracking-wider">
                        All new listings are reviewed by administrators to ensure heritage quality standards before appearing in the shop.
                    </p>
                </div>
                <button type="submit" class="w-full py-5 bg-black text-white rounded-2xl font-bold uppercase tracking-[0.2em] shadow-xl hover:bg-[#C0420A] transition-all">
                    Submit Listing
                </button>
            </div>
        </div>
    </form>
</div>

<script>
function previewImages(input) {
    const container = document.getElementById('image-preview-container');
    const placeholder = document.getElementById('upload-placeholder');
    container.innerHTML = '';
    
    if (input.files && input.files.length > 0) {
        container.classList.remove('hidden');
        container.classList.add('grid');
        placeholder.classList.add('hidden');
        
        Array.from(input.files).forEach(file => {
            const reader = new FileReader();
            reader.onload = function(e) {
                const div = document.createElement('div');
                div.className = 'aspect-3/4 rounded-lg overflow-hidden border border-gray-100';
                div.innerHTML = `<img src="${e.target.result}" class="w-full h-full object-cover">`;
                container.appendChild(div);
            }
            reader.readAsDataURL(file);
        });
    } else {
        container.classList.add('hidden');
        container.classList.remove('grid');
        placeholder.classList.remove('hidden');
    }
}
</script>
@endsection
