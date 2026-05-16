@extends('layouts.seller')

@section('content')
<div class="max-w-[1400px] mx-auto" x-data="{ deleteModal: false, deleteProductId: null, deleteProductName: '' }">
    <div class="mb-10">
        <a href="{{ route('seller.products.index') }}" class="inline-flex items-center gap-2 text-[10px] font-bold text-gray-400 uppercase tracking-widest hover:text-[#C0420A] transition-colors mb-4">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path></svg>
            Back to catalogue
        </a>
        <h1 class="font-serif text-3xl font-bold text-black uppercase">Edit <span class="text-[#C0420A] italic lowercase">heritage piece</span></h1>
    </div>

    @if($errors->any())
        <div class="mb-8 p-6 bg-red-50 border border-red-100 rounded-2xl">
            <ul class="space-y-1">
                @foreach($errors->all() as $error)
                    <li class="text-xs text-red-600 font-bold">• {{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form action="{{ route('seller.products.update', $product->id) }}" method="POST" enctype="multipart/form-data" class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        @csrf
        @method('PUT')

        {{-- Left: Main Details --}}
        <div class="lg:col-span-2 space-y-8">
            <div class="bg-white p-8 rounded-3xl border border-gray-100 shadow-sm space-y-8">
                <div class="space-y-4">
                    <label class="text-[10px] font-bold uppercase tracking-widest text-gray-400">Product Name</label>
                    <input type="text" name="name" required value="{{ old('name', $product->name) }}"
                        placeholder="e.g. Pina-Silk Formal Barong Tagalog"
                        class="w-full px-6 py-4 bg-gray-50/50 border border-gray-100 rounded-2xl outline-none focus:border-[#C0420A] transition-all font-medium text-lg">
                </div>

                <div class="space-y-4">
                    <label class="text-[10px] font-bold uppercase tracking-widest text-gray-400">Artisan Description</label>
                    <textarea name="description" required rows="6"
                        placeholder="Describe the craftsmanship, materials, and the story..."
                        class="w-full px-6 py-4 bg-gray-50/50 border border-gray-100 rounded-2xl outline-none focus:border-[#C0420A] transition-all font-medium resize-none">{{ old('description', $product->description) }}</textarea>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                    <div class="space-y-4">
                        <label class="text-[10px] font-bold uppercase tracking-widest text-gray-400">Price (₱)</label>
                        <input type="number" name="price" required min="1" step="0.01"
                            value="{{ old('price', $product->price) }}"
                            class="w-full px-6 py-4 bg-gray-50/50 border border-gray-100 rounded-2xl outline-none focus:border-[#C0420A] transition-all font-bold text-xl">
                    </div>
                    <div class="space-y-4">
                        <label class="text-[10px] font-bold uppercase tracking-widest text-gray-400">Stock Quantity</label>
                        <input type="number" name="stock" required min="0"
                            value="{{ old('stock', $product->stock) }}"
                            class="w-full px-6 py-4 bg-gray-50/50 border border-gray-100 rounded-2xl outline-none focus:border-[#C0420A] transition-all font-bold text-xl">
                    </div>
                </div>

                <div class="space-y-4">
                    <label class="text-[10px] font-bold uppercase tracking-widest text-gray-400">Product Category</label>
                    <select name="CategoryId" required class="w-full px-6 py-4 bg-gray-50/50 border border-gray-100 rounded-2xl outline-none focus:border-[#C0420A] transition-all font-bold text-sm appearance-none">
                        <option value="" disabled>Select a category</option>
                        @foreach($categories as $category)
                            <option value="{{ $category->id }}" {{ $product->CategoryId == $category->id ? 'selected' : '' }}>
                                {{ $category->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
            </div>

            {{-- Sizing --}}
            <div class="bg-white p-8 rounded-3xl border border-gray-100 shadow-sm space-y-6">
                <h3 class="text-sm font-bold text-black uppercase tracking-widest">Heritage Sizing</h3>
                @php
                    $currentSizes = is_array($product->sizes) ? $product->sizes : (json_decode($product->sizes ?? '[]', true) ?? []);
                @endphp
                <div class="flex flex-wrap gap-3">
                    @foreach(['S', 'M', 'L', 'XL', 'XXL', 'Custom'] as $size)
                        <label class="cursor-pointer group">
                            <input type="checkbox" name="sizes[]" value="{{ $size }}" class="hidden peer"
                                {{ in_array($size, $currentSizes) ? 'checked' : '' }}>
                            <div class="px-6 py-3 rounded-xl border border-gray-100 bg-gray-50/50 text-xs font-bold text-gray-400 peer-checked:bg-[#C0420A] peer-checked:text-white peer-checked:border-[#C0420A] peer-checked:shadow-lg peer-checked:shadow-[#C0420A]/20 transition-all">
                                {{ $size }}
                            </div>
                        </label>
                    @endforeach
                </div>
            </div>

            {{-- Existing Images --}}
            @php
                $images = is_array($product->image) ? $product->image : (json_decode($product->image ?? '[]', true) ?? []);
                if (is_string($product->image) && !str_starts_with($product->image, '[')) {
                    $images = [$product->image];
                }
            @endphp
            @if(count($images) > 0)
            <div class="bg-white p-8 rounded-3xl border border-gray-100 shadow-sm space-y-6">
                <h3 class="text-sm font-bold text-black uppercase tracking-widest">Current Images</h3>
                <div class="grid grid-cols-3 md:grid-cols-4 gap-4">
                    @foreach($images as $i => $img)
                        @php
                            $imgSrc = str_starts_with($img, 'http') ? $img : asset('uploads/products/' . $img);
                        @endphp
                        <div class="relative group aspect-3/4 rounded-xl overflow-hidden border border-gray-100">
                            <img src="{{ $imgSrc }}" class="w-full h-full object-cover">
                            <label class="absolute inset-0 bg-black/50 opacity-0 group-hover:opacity-100 flex items-center justify-center cursor-pointer transition-opacity">
                                <input type="checkbox" name="remove_images[]" value="{{ $img }}" class="hidden peer">
                                <div class="px-3 py-1.5 rounded-lg bg-red-500 text-white text-[9px] font-black uppercase tracking-widest peer-checked:bg-red-700">
                                    Remove
                                </div>
                            </label>
                        </div>
                    @endforeach
                </div>
                <p class="text-[10px] text-gray-400">Hover over an image and tick "Remove" to delete it when you save.</p>
            </div>
            @endif
        </div>

        {{-- Right: Image Upload & Actions --}}
        <div class="space-y-8">
            <div class="bg-white p-8 rounded-3xl border border-gray-100 shadow-sm space-y-6">
                <h3 class="text-sm font-bold text-black uppercase tracking-widest">Add New Images</h3>
                <div class="aspect-3/4 rounded-2xl border-2 border-dashed border-gray-100 bg-gray-50/50 flex flex-col items-center justify-center p-8 text-center group hover:bg-white hover:border-[#C0420A] transition-all cursor-pointer relative overflow-hidden">
                    <input type="file" name="images[]" multiple class="absolute inset-0 opacity-0 cursor-pointer" onchange="previewImages(this)">
                    <div id="upload-placeholder" class="space-y-2">
                        <svg class="w-10 h-10 text-gray-200 mx-auto group-hover:text-[#C0420A] transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                        <div class="text-[10px] font-bold text-gray-400 uppercase tracking-widest">Add More Photos</div>
                        <p class="text-[9px] text-gray-300">Optional — only if replacing or adding.</p>
                    </div>
                    <div id="image-preview-container" class="hidden grid-cols-2 gap-2 w-full h-full p-2 bg-white absolute inset-0 overflow-y-auto no-scrollbar"></div>
                </div>
            </div>

            <div class="bg-white p-8 rounded-3xl border border-gray-100 shadow-sm space-y-4">
                <h3 class="text-sm font-bold text-black uppercase tracking-widest">Listing Status</h3>
                <div class="flex items-center gap-3 p-4 rounded-2xl bg-gray-50 border border-gray-100">
                    <div class="w-2.5 h-2.5 rounded-full {{ $product->status === 'approved' ? 'bg-green-500' : 'bg-amber-500' }}"></div>
                    <span class="text-xs font-black uppercase tracking-widest {{ $product->status === 'approved' ? 'text-green-600' : 'text-amber-600' }}">
                        {{ ucfirst($product->status) }}
                    </span>
                </div>
                @if($product->status !== 'approved')
                <p class="text-[10px] text-gray-400 italic">Editing this listing will re-submit it for admin review.</p>
                @endif

                <button type="submit"
                    class="w-full py-5 bg-black text-white rounded-2xl font-bold uppercase tracking-[0.2em] shadow-xl hover:bg-[#C0420A] transition-all">
                    Save Changes
                </button>

                <button type="button"
                    @click="deleteModal = true; deleteProductId = '{{ $product->id }}'; deleteProductName = '{{ addslashes($product->name) }}'"
                    class="w-full py-4 border-2 border-red-100 text-red-400 rounded-2xl font-bold text-[10px] uppercase tracking-widest hover:bg-red-50 hover:border-red-200 hover:text-red-600 transition-all">
                    Delete Listing
                </button>
            </div>
        </div>
    </form>

    {{-- Delete Confirmation Modal --}}
    <div x-show="deleteModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/40 backdrop-blur-sm" x-cloak>
        <div @click.away="deleteModal = false" class="bg-white rounded-3xl shadow-2xl w-full max-w-md p-8 space-y-6">
            <div class="text-center">
                <div class="w-16 h-16 rounded-full bg-red-50 flex items-center justify-center mx-auto mb-4">
                    <svg class="w-8 h-8 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                </div>
                <h3 class="font-serif text-xl font-bold text-black mb-1">Delete This Listing?</h3>
                <p class="text-xs text-gray-500" x-text="'\"' + deleteProductName + '\" will be permanently removed.'"></p>
            </div>
            <form :action="'/seller/products/' + deleteProductId" method="POST" class="flex gap-3">
                @csrf
                @method('DELETE')
                <button type="button" @click="deleteModal = false"
                    class="flex-1 py-3 rounded-xl border border-gray-200 text-[10px] font-bold uppercase tracking-widest text-gray-500 hover:bg-gray-50 transition-all">
                    Keep Listing
                </button>
                <button type="submit"
                    class="flex-1 py-3 rounded-xl bg-red-500 text-white text-[10px] font-bold uppercase tracking-widest hover:bg-red-600 transition-all">
                    Yes, Delete
                </button>
            </form>
        </div>
    </div>
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
            reader.onload = e => {
                const div = document.createElement('div');
                div.className = 'aspect-3/4 rounded-lg overflow-hidden border border-gray-100';
                div.innerHTML = `<img src="${e.target.result}" class="w-full h-full object-cover">`;
                container.appendChild(div);
            };
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
